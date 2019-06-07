<?php

namespace App\Http\Controllers;

use App\Assignee;
use App\Jobs\SendProjectEmail;
use App\Project;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectsController extends Controller
{
    const assigneeRules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255'],
    ];
    const addedRules = [
        ['unique:assignees,email'],
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $projects = Project::select('id', 'name', 'state');
        if (request()->has('state') && in_array($state = request('state'), Project::STATES)) {
            $projects = $projects->where('state', $state)->paginate(10)->appends('state', $state);
        } else {
            $projects = $projects->paginate(10);
        }
        return view('projects', compact('projects'));
    }

    /**
     * Show the project page.
     *
     * @param string $id
     * @return Response
     */
    public function show($id)
    {
        $project = Project::select('id', 'name', 'description', 'state')->where('id', $id)->firstOrFail();
        return view('project')->with('project', $project);
    }

    /**
     * Handle POST request for deleting a project from the database.
     *
     * @return Response
     */
    public function delete()
    {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        try {
            $this->deleteProject(request('id'));
            return response('OK', 200, ['Content-Type' => 'text/plain']);
        } catch (QueryException $ex) {
            return response($ex->getMessage(), 500, ['Content-Type' => 'text/plain']);
        }
    }

    /**
     * Delete a project from the database.
     *
     * @param string $id
     */
    protected function deleteProject($id)
    {
        $assignees = Assignee::where('assigned_to', $id)->get();
        $projectName = Project::select('name')->where('id', $id)->firstOrFail();
        foreach ($assignees as $assignee) {
            dispatch(new SendProjectEmail($assignee->email, 'deleted', $projectName));
        }
        Assignee::where('assigned_to', $id)->delete();
        Project::where('id', $id)->delete();
    }

    /**
     * Handle GET request for deleting a project from the database.
     *
     * @param string $id
     * @return Response
     */
    public function deleteNoJS($id)
    {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        $project = Project::select('id', 'name')->where('id', $id)->firstOrFail();
        $this->deleteProject($id);
        return view('deletion')->with(
            [
                'project' => $project,
                'redirect' => route('projects.index'),
            ]
        );
    }

    /**
     * Show the project edit page.
     *
     * @param string $id
     * @return Response
     */
    public function showEditForm($id)
    {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        $project = Project::select('id', 'name', 'description', 'state')->where('id', $id)->firstOrFail();
        return view('edit')->with('project', $project);
    }

    /**
     * Maps email addresses to a bitmask that shows changes.
     * 0b00 (0) - N/A
     * 0b01 (1) - Email only exists in the database. It needs to be removed.
     * 0b10 (2) - Email only exists in the POST. It needs to be added.
     * 0b11 (3) - Email exists both in POST and database. No action required other than sending an email.
     *
     * @param array $assignees
     * @param string $id
     * @return array
     */
    protected function createEmailMasks($assignees, $id)
    {
        $map = [];
        foreach (Assignee::email($id)->get() as $existing) {
            $map[$existing->email] = 1;
        }
        foreach ($assignees as $assignee) {
            $email = $assignee['email'];
            if (isset($map[$email])) {
                switch ($map[$email]) {
                    case 1:
                        $map[$email] |= 2;
                        break;
                    default:
                        throw ValidationException::withMessages(
                            [
                                'email' => $email . ' is already in use.',
                            ]
                        );
                }
            } else {
                $map[$email] = 2;
            }
        }
        return $map;
    }

    /**
     * Bulk insert assignees into the database.
     *
     * @param array $assignees
     * @param array $added
     * @param string $id
     */
    protected function addAssignees($assignees, $added, $id)
    {
        if (count($added) <= 0) return;
        Validator::make($added, static::addedRules)->validate();
        $getName = function ($email) use ($assignees) {
            foreach ($assignees as $assignee) {
                if ($assignee['email'] === $email) {
                    return $assignee['name'];
                }
            }
            return null;
        };
        $addedAssignees = [];
        foreach ($added as $addedEmail) {
            $addedAssignees[] = [
                'name' => $getName($addedEmail),
                'email' => $addedEmail,
                'assigned_to' => $id,
            ];
        }
        Assignee::insert($addedAssignees);
    }

    /**
     * Validate and commit changes made to a project and its assignees.
     *
     * @param string $id
     * @return Response
     */
    public function edit($id)
    {
        if (!Auth::check()) {
            return redirect(route('projects.show', ['id' => $id]));
        }
        $assignees = $this->getValidatedAssignees($id);
        $emailMasks = $this->createEmailMasks($assignees, $id);
        $projectName = request('name');

        $emailsToSend = [];
        $added = [];
        $removed = [];
        $unchanged = [];
        $varMap = [1 => 'removed', 2 => 'added', 3 => 'unchanged'];
        foreach ($emailMasks as $email => $mask) {
            if ($mask !== 3) {
                $emailsToSend[] = new SendProjectEmail($email, $varMap[$mask], $projectName);
            }
            ${$varMap[$mask]}[] = $email;
        }

        $this->addAssignees($assignees, $added, $id);
        if (count($removed) > 0) {
            Assignee::whereIn('email', $removed)->delete();
        }

        foreach ($emailsToSend as $email) {
            dispatch($email);
        }

        [$changed, $updatedData] = $this->diffProjectChanges($id);
        if (count($changed) > 0) {
            foreach ($unchanged as $email) {
                dispatch(new SendProjectEmail($email, 'changed', $projectName, $changed));
            }
            Project::where('id', $id)->update($updatedData);
        }

        return redirect(route('projects.show', ['id' => $id]));
    }

    /**
     * Show the project creation page.
     *
     * @return Response
     */
    public function showCreateForm()
    {
        return Auth::check() ?
            view('create') :
            redirect(route('projects.index'));
    }

    /**
     * Handle POST request for creating a project.
     *
     * @return Response
     */
    public function create()
    {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        $assignees = $this->getValidatedAssignees();

        $projectData = [];
        $projectKeys = ['name', 'state', 'description'];
        foreach ($projectKeys as $key) {
            $projectData[$key] = request($key) ?? '';
        }

        $newProject = Project::create($projectData);
        $id = $newProject->id;
        $projectName = $projectData['name'];

        $emailsToSend = [];
        foreach ($assignees as $assignee) {
            $emailsToSend[] = new SendProjectEmail($assignee['email'], 'added', $projectName);
            $assignee['assigned_to'] = $id;
        }
        Assignee::insert($assignees);

        foreach ($emailsToSend as $email) {
            dispatch($email);
        }

        return redirect(route('projects.show', ['id' => $id]));
    }

    /**
     * Retrieve assignees from POST request or fail with validation error.
     *
     * @param string $id
     * @return array
     */
    protected function getValidatedAssignees($id = null)
    {
        $assignees = $this->processAssignees(request('assignees'));
        $this->validateRequest($assignees, $id);
        return $assignees;
    }

    /**
     * Process the text field's contents with regular expression. This is done this way to support clients without
     * JavaScript support.
     *
     * @param string $assignees
     * @return array
     */
    protected function processAssignees($assignees)
    {
        if (is_null($assignees)) {
            return [];
        }
        $pattern = '/([^\n\r]+)\r?\n([^\n\r]+)(?:(?:\r?\n){2})?/';
        $result = [];
        preg_match_all($pattern, $assignees, $result, PREG_SET_ORDER);
        foreach ($result as &$match) {
            array_shift($match);
            $match = [
                'name' => $match[0],
                'email' => $match[1],
            ];
        }
        return $result;
    }

    /**
     * Validate the processed assignees and the project details.
     *
     * @param array $assignees
     * @param string $id
     */
    protected function validateRequest($assignees, $id = null)
    {
        foreach ($assignees as $assignee) {
            Validator::make($assignee, static::assigneeRules)->validate();
        }
        if (is_null($id)) {
            $this->validateProject();
        } else {
            $this->validateProject(Rule::unique('projects')->ignore($id));
        }
    }

    /**
     * Validate project details.
     *
     * @param Rule $extra Extra rule to use in validation.
     */
    protected function validateProject($extra = null)
    {
        $nameRules = ['required', 'string', 'max:255'];
        if (!is_null($extra)) {
            array_push($nameRules, $extra);
        }
        Validator::make(
            request(['name', 'description', 'state']),
            [
                'name' => $nameRules,
                'description' => ['nullable', 'string', 'max:4000'],
                'state' => ['required', Rule::in(Project::STATES)],
            ]
        )->validate();
    }

    /**
     * Create a diff of changes made to project details.
     *
     * @param string $id
     * @return array
     */
    protected function diffProjectChanges($id)
    {
        $updatedData = [];
        foreach (Project::FILLABLE as $key) {
            $updatedData[$key] = request($key);
        }
        $project = Project::select(Project::FILLABLE)->where('id', $id)->firstOrFail();
        $changed = [];
        foreach ($updatedData as $key => $value) {
            if ($project[$key] !== $value) {
                $changed[$key] = $value;
            }
        }
        return [$changed, $updatedData];
    }
}
