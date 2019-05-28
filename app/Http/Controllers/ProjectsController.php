<?php

namespace App\Http\Controllers;

use App\Assignee;
use App\Jobs\SendProjectEmail;
use App\Project;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectsController extends Controller {
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
    public function __construct() {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() {
        if (request()->has('state') && in_array($state = request('state'), Project::states)) {
            $projects = Project::select('id', 'name', 'state')->where('state', $state)->paginate(10)
                ->appends('state', $state);
        }
        else {
            $projects = Project::select('id', 'name', 'state')->paginate(10);
        }
        return view('projects', compact('projects'));
    }

    /**
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $project = Project::select('id', 'name', 'description', 'state')->where('id', $id)->firstOrFail();
        return view('project')->with('project', $project);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function delete() {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        try {
            $this->deleteProject(request('id'));
            return response('OK', 200, ['Content-Type' => 'text/plain']);
        }
        catch (QueryException $ex) {
            return response($ex->getMessage(), 500, ['Content-Type' => 'text/plain']);
        }
    }

    /**
     * @param string $id
     */
    protected function deleteProject($id) {
        $assignees = Assignee::where('assigned_to', $id)->get();
        $projectName = Project::select('name')->where('id', $id)->firstOrFail();
        foreach ($assignees as $assignee) {
            dispatch(new SendProjectEmail($assignee->email, 'deleted', $projectName));
        }
        Assignee::where('assigned_to', $id)->delete();
        Project::where('id', $id)->delete();
    }

    /**
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function deleteNoJS($id) {
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
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function showEditForm($id) {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        $project = Project::select('id', 'name', 'description', 'state')->where('id', $id)->firstOrFail();
        return view('edit')->with('project', $project);
    }

    /**
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if (!Auth::check()) {
            return redirect(route('projects.show', ['id' => $id]));
        }
        $assignees = $this->getValidatedAssignees($id);

        $map = [];
        foreach (Assignee::select('email')->where('assigned_to', $id)->get() as $existing) {
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
            }
            else {
                $map[$email] = 2;
            }
        }

        $emailsToSend = [];
        $added = [];
        $removed = [];
        $unchanged = [];
        $projectName = request('name');
        $varMap = [1 => 'removed', 2 => 'added', 3 => 'unchanged'];
        foreach ($map as $email => $mask) {
            if ($mask !== 3) {
                $emailsToSend[] = new SendProjectEmail($email, $varMap[$mask], $projectName);
            }
            ${$varMap[$mask]}[] = $email;
        }
        if (count($added) > 0) {
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
        if (count($removed) > 0) {
            Assignee::whereIn('email', $removed)->delete();
        }

        $updatedData = [];
        $projectKeys = ['name', 'state', 'description'];
        foreach ($projectKeys as $key) {
            $updatedData[$key] = request($key);
        }
        $project = Project::select($projectKeys)->where('id', $id)->firstOrFail();
        $changed = [];
        foreach ($updatedData as $key => $value) {
            if ($project[$key] !== $value) {
                $changed[] = $value;
            }
        }
        foreach ($emailsToSend as $email) {
            dispatch($email);
        }
        if (count($changed) > 0) {
            foreach ($unchanged as $email) {
                dispatch(new SendProjectEmail($email, 'changed', $projectName, $changed));
            }
            Project::where('id', $id)->update($updatedData);
        }

        return redirect(route('projects.show', ['id' => $id]));
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function showCreateForm() {
        return Auth::check() ?
            view('create') :
            redirect(route('projects.index'));
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function create() {
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
            $assigneeData = array_slice($assignee, 0);
            $assigneeData['assigned_to'] = $id;
            Assignee::create($assigneeData);
        }

        foreach ($emailsToSend as $email) {
            dispatch($email);
        }

        return redirect(route('projects.show', ['id' => $id]));
    }

    /**
     * @param string $id
     * @return array
     */
    protected function getValidatedAssignees($id = null) {
        $assignees = $this->processAssignees(request('assignees'));
        $this->validateRequest($assignees, $id);
        return $assignees;
    }

    /**
     * @param string $assignees
     * @return array
     */
    protected function processAssignees($assignees) {
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
     * @param array $assignees
     * @param string $id
     * @return void
     */
    protected function validateRequest(array $assignees, $id = null) {
        foreach ($assignees as $assignee) {
            Validator::make($assignee, static::assigneeRules)->validate();
        }
        if (is_null($id)) {
            $this->validateProject();
        }
        else {
            $this->validateProject(Rule::unique('projects')->ignore($id));
        }
    }

    /**
     * @param mixed $extra
     */
    protected function validateProject($extra = null) {
        $nameRules = ['required', 'string', 'max:255'];
        if (!is_null($extra)) {
            array_push($nameRules, $extra);
        }
        Validator::make(
            request(['name', 'description', 'state']),
            [
                'name' => $nameRules,
                'description' => ['nullable', 'string', 'max:4000'],
                'state' => ['required', Rule::in(Project::states)],
            ]
        )->validate();
    }
}
