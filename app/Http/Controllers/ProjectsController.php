<?php

namespace App\Http\Controllers;

use App\Assignee;
use App\Http\Requests\ProjectRequest;
use App\Jobs\SendProjectEmail;
use App\Mail\ProjectEmail;
use App\Project;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request)
    {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        try {
            $this->deleteProject($request->get('id'));
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
        $assignees = Assignee::where('project_id', $id)->get();
        $projectName = Project::select('name')->where('id', $id)->firstOrFail();
        foreach ($assignees as $assignee) {
            $this->dispatch(new SendProjectEmail($assignee->email, 'deleted', $projectName));
        }
        Assignee::where('project_id', $id)->delete();
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
     * Bulk insert assignees into the database.
     *
     * @param Collection $assignees
     * @param string $projectName
     * @param string $id
     */
    protected function addAssignees($assignees, $projectName, $id)
    {
        Assignee::insert($assignees->map(function ($assignee) use ($projectName, $id) {
            $this->dispatch(new SendProjectEmail($assignee['email'], 'added', $projectName));
            return [
                'name' => $assignee['name'],
                'email' => $assignee['email'],
                'project_id' => $id,
            ];
        })->toArray());
    }

    /**
     * Validate and commit changes made to a project and its assignees.
     *
     * @param ProjectRequest $request
     * @param string $id
     * @return Response
     */
    public function edit(ProjectRequest $request, $id)
    {
        if (!Auth::check()) {
            return redirect(route('projects.show', ['id' => $id]));
        }
        /** @var array $validated */
        /** @var string $projectName */
        /** @var Model $project */
        $validated = $request->validated();
        $projectName = $validated['name'];
        $project = Project::where('id', $id)->firstOrFail();

        $assigneesInRequest = collect($this->processAssignees($validated['assignees']));
        $assigneesInDatabase = collect(Assignee::nameAndEmail($id)->get());

        $diffEmails = function ($a, $b) {
            return $a['email'] === $b['email'] ? 0 : 1;
        };
        $assigneesAdded = $assigneesInDatabase->diffUsing($assigneesInRequest, $diffEmails);
        $assigneesRemoved = $assigneesInRequest->diffUsing($assigneesInDatabase, $diffEmails);

        $this->addAssignees($assigneesAdded, $projectName, $id);
        $this->removeAssignees($assigneesRemoved, $projectName);
        $project->fill(array_filter($validated, function ($_, $key) {
            return $key !== 'assignees';
        }));
        if ($project->isDirty()) {
            $dirty = $project->getDirty();
            $assigneesInDatabase->diffUsing($assigneesInRequest, function ($a, $b) {
                return $a['email'] === $b['email'] ? 1 : 0;
            })->each(function ($assignee) use ($projectName, $dirty) {
                $this->dispatch(new SendProjectEmail($assignee['name'], 'changed', $projectName, $dirty));
            });
            $project->save();
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
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        if (!Auth::check()) {
            return redirect(route('projects.index'));
        }
        $assignees = $this->getValidatedAssignees($request);

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
            $assignee['project_id'] = $id;
        }
        Assignee::insert($assignees);

        foreach ($emailsToSend as $email) {
            dispatch($email);
        }

        return redirect(route('projects.show', ['id' => $id]));
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
     * @param Request $request
     * @param array $assignees
     * @param string $id
     */
    protected function validateRequest($request, $assignees, $id = null)
    {
        foreach ($assignees as $assignee) {
            Validator::make($assignee, static::assigneeRules)->validate();
        }
        if (is_null($id)) {
            $this->validateProject($request);
        } else {
            $this->validateProject($request, Rule::unique('projects')->ignore($id));
        }
    }

    /**
     * Validate project details.
     *
     * @param Request $request
     * @param Rule $extra Extra rule to use in validation.
     */
    protected function validateProject($request, $extra = null)
    {
        $nameRules = ['required', 'string', 'max:255'];
        if (!is_null($extra)) {
            array_push($nameRules, $extra);
        }
        Validator::make(
            $request->only(['name', 'description', 'state']),
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
     * @param Request $request
     * @param string $id
     * @return array
     */
    protected function diffProjectChanges($request, $id)
    {
        $updatedData = [];
        foreach (Project::FILLABLE as $key) {
            $updatedData[$key] = $request->get($key);
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

    /**
     * @param Collection $assigneesRemoved
     * @param string $projectName
     */
    protected function removeAssignees($assigneesRemoved, $projectName)
    {
        $emails = $assigneesRemoved->each(function ($email) use ($projectName) {
            $this->dispatch(new SendProjectEmail($email, 'removed', $projectName));
        })->toArray();
        Assignee::whereIn('email', $emails)->delete();
    }
}
