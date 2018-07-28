<?php

namespace App\Http\Controllers;
use App\UserTasks;
use App\AdminTasks;
use App\AssignTasks;
use DB;
use Auth;
use App\User;
use App\Http\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\TaskMigrate;
use Illuminate\Http\Request;
use App\Http\Controllers\View;
use Carbon\Carbon;
use App\Http\Controllers\student\StudentController;

class TaskMigrateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
            $assign_tasks = AssignTasks::orderBy('id','DESC')
            ->join('admin_tasks','assign_tasks.task_id', '=', 'admin_tasks.id')
            ->where('assign_tasks.status','=','review')
            ->where('assign_tasks.guide_id',Auth::user()->id)

            // ->where(function ($query) {
            //     $query->where('assign_tasks.assigned_by_userid',Auth::user()->id)
            //           ->orWhere('assign_tasks.guide_id',Auth::user()->id);
            //         //   ->orWhere('assign_tasks.reviewer_id',Auth::user()->id);
            // })

            ->join('users as users_u','users_u.id','assign_tasks.user_id')
            ->join('users as users_s','users_s.id','assign_tasks.assigned_by_userid')
            ->join('users as users_g','users_g.id','assign_tasks.guide_id')
            ->join('users as users_r','users_r.id','assign_tasks.reviewer_id')

            ->select('assign_tasks.*','admin_tasks.worktitle','admin_tasks.workdescription','admin_tasks.whatinitforme','admin_tasks.usercredits','admin_tasks.uploads','users_u.first_name as first_name','users_u.last_name as last_name','users_s.name as sname','users_g.name as gname','users_r.name as rname')
            ->orderBy('assign_tasks.task_id','desc')->get();  

            $review = $assign_tasks->count();
            $redo = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','redo')->where('assign_tasks.reviewer_id',Auth::user()->id)->count();
            $review_for_approve = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review_for_approve')->where('assign_tasks.reviewer_id',Auth::user()->id)->count();
            $drop = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','drop')->where('assign_tasks.reviewer_id',Auth::user()->id)->count();
            $approved = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','approved')->where('assign_tasks.guide_id',Auth::user()->id)->count();
            // return $approved;

        
       
        return view('TaskMigrate.index',compact('assign_tasks','review','redo','review_for_approve','drop','approved'));
            
        
    
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
     //   
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $reserved_credits=0;
        $this->validate($request, [
            'assigntask_id' => 'required',
            'request_for' => 'required',
            'request_by' => 'required',
            'rating_to_user' => 'nullable',
            'rating_to_guide' => 'nullable',
            'message' => 'required',
            'uploads' => 'file | mimes:rar,zip,jpg,jpeg,png,pdf,ppt,pptx,xls,xlsx,doc,docx,bmp |max:5120',
            'created_at' => '',

        ]);

        $task = new UserTasks;
        $task->assigntask_id = $request->assigntask_id;
        $task->request_for = $request->request_for;
        $task->request_by = $request->request_by;
        $task->rating_to_user = $request->rating_to_user;
        $task->rating_to_guide = $request->rating_to_guide;
        $task->message = $request->message;
        if($request->hasFile('uploads')) {
            $task->uploads = storeFile($request->uploads,'uploads');
            }
        $task->created_at = $request->created_at;

        // return $task;
        

        if($task->request_for =='approved'){
            $reserved_credits=DB::table('assign_tasks')->where('assign_tasks.id', $task->assigntask_id) 
            ->join('admin_tasks','assign_tasks.task_id','admin_tasks.id')
            ->select('admin_tasks.usercredits')->get()->pluck('usercredits')[0];

            $reserved_guide_credits=DB::table('assign_tasks')->where('assign_tasks.id', $task->assigntask_id) 
            ->join('admin_tasks','assign_tasks.task_id','admin_tasks.id')
            ->select('admin_tasks.guidecredits')->get()->pluck('guidecredits')[0];

            $reserved_reviewer_credits=DB::table('assign_tasks')->where('assign_tasks.id', $task->assigntask_id) 
            ->join('admin_tasks','assign_tasks.task_id','admin_tasks.id')
            ->select('admin_tasks.reviewercredits')->get()->pluck('reviewercredits')[0];

            DB::table('assign_tasks')->where('id', $task->assigntask_id)  
            ->update(['user_credits' => $task->rating_to_user * $reserved_credits/10,'guide_credits' => $task->rating_to_guide * $reserved_guide_credits/10,
            'reviewer_credits' => 10*$reserved_guide_credits/10,
            'status' => $task->request_for,'completed_at' => Carbon::now('Asia/Kolkata')]); 
        }

        DB::table('assign_tasks')->where('id', $task->assigntask_id)  
        ->update(['status' => $task->request_for,'completed_at' => Carbon::now('Asia/Kolkata')]);

    
        
        unset($task->rating_to_user);//removed as there is no column of obtained marks 
        unset($task->rating_to_guide);

        $task->save();

        // This updates course score after quiz submission
        $course_id= coursetask::where('task_id','assign_tasks.task_id')
         ->join('chapters','chapters.id','coursetasks.chapter_id')
         ->find('chapters.course_id');
         StudentController::UpdateScore($course_id,Auth::user()->id);
 
    return redirect()->route('TaskMigrate.index');
                   
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\TaskMigrate  $taskMigrate
     * @return \Illuminate\Http\Response
     */
    public function show($cop_str)
    {   
            if($cop_str == 'review_for_approve')
            {
                $assign_tasks = AssignTasks::orderBy('id','DESC')
                ->join('admin_tasks','assign_tasks.task_id', '=', 'admin_tasks.id')
                ->where('assign_tasks.status',$cop_str)
                ->where('assign_tasks.reviewer_id',Auth::user()->id)

                ->join('users as users_u','users_u.id','assign_tasks.user_id')
                ->join('users as users_s','users_s.id','assign_tasks.assigned_by_userid')
                ->join('users as users_g','users_g.id','assign_tasks.guide_id')
                ->join('users as users_r','users_r.id','assign_tasks.reviewer_id')

                ->select('assign_tasks.*','admin_tasks.worktitle','admin_tasks.workdescription','admin_tasks.whatinitforme','admin_tasks.usercredits','admin_tasks.uploads','users_u.first_name as first_name','users_u.last_name as last_name','users_s.name as sname','users_g.name as gname','users_r.name as rname')
                ->get();

                $review = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                $redo = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','redo')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                $review_for_approve = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review_for_approve')->where('assign_tasks.reviewer_id',Auth::user()->id)->count();
                $drop = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','drop')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                $approved = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','approved')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                

            }
            else{

                $assign_tasks = AssignTasks::orderBy('id','DESC')
                ->join('admin_tasks','assign_tasks.task_id', '=', 'admin_tasks.id')
                ->where('assign_tasks.status',$cop_str)
                ->where('assign_tasks.guide_id',Auth::user()->id)

                ->join('users as users_u','users_u.id','assign_tasks.user_id')
                ->join('users as users_s','users_s.id','assign_tasks.assigned_by_userid')
                ->join('users as users_g','users_g.id','assign_tasks.guide_id')
                ->join('users as users_r','users_r.id','assign_tasks.reviewer_id')
 
                ->select('assign_tasks.*','admin_tasks.worktitle','admin_tasks.workdescription','admin_tasks.whatinitforme','admin_tasks.usercredits','admin_tasks.uploads','users_u.first_name as first_name','users_u.last_name as last_name','users_s.name as sname','users_g.name as gname','users_r.name as rname')
                ->get();

                $review = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                $redo = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','redo')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                $review_for_approve = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review_for_approve')->where('assign_tasks.reviewer_id',Auth::user()->id)->count();
                $drop = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','drop')->where('assign_tasks.guide_id',Auth::user()->id)->count();
                $approved = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','approved')->where('assign_tasks.guide_id',Auth::user()->id)->count();


            }
            // else if($cop_str == 'drop'|| 'approved'){

            //     $assign_tasks = AssignTasks::orderBy('id','DESC')
            //     ->join('admin_tasks','assign_tasks.task_id', '=', 'admin_tasks.id')
            //     ->where('assign_tasks.status',$cop_str)
            //     ->where('assign_tasks.guide_id',Auth::user()->id)
            //         // ->where('assign_tasks.assigned_by_userid',Auth::user()->id)
            //         //       ->orWhere('assign_tasks.guide_id',Auth::user()->id)
            //         //       ->orWhere('assign_tasks.reviewer_id',Auth::user()->id);
                

            //     ->join('users as users_u','users_u.id','assign_tasks.user_id')
            //     ->join('users as users_s','users_s.id','assign_tasks.assigned_by_userid')
            //     ->join('users as users_g','users_g.id','assign_tasks.guide_id')
            //     ->join('users as users_r','users_r.id','assign_tasks.reviewer_id')

            //     ->select('assign_tasks.*','admin_tasks.worktitle','admin_tasks.workdescription','admin_tasks.whatinitforme','admin_tasks.usercredits','admin_tasks.uploads','users_u.name','users_s.name as sname','users_g.name as gname','users_r.name as rname')
            //     ->get();

            //     $review = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review')->where('assign_tasks.guide_id',Auth::user()->id)->count();
            //     $redo = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','redo')->where('assign_tasks.guide_id',Auth::user()->id)->count();
            //     $review_for_approve = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','review_for_approve')->where('assign_tasks.reviewer_id',Auth::user()->id)->count();
            //     $drop = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','drop')->where('assign_tasks.guide_id',Auth::user()->id)->count();
            //     $approved = AssignTasks::orderBy('id','DESC')->where('assign_tasks.status','approved')->where('assign_tasks.guide_id',Auth::user()->id)->count();

            // }
            
                       
        return view('TaskMigrate.index',compact('assign_tasks','review','redo','review_for_approve','drop','approved'));
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TaskMigrate  $taskMigrate
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,$id)
    {
        $task_id = $request->task_id;

        $task_details = AdminTasks::find($task_id);
        $user_tasks = UserTasks::orderBy('id','ASC')
        
        ->join('assign_tasks','user_tasks.assigntask_id', '=', 'assign_tasks.id')

        ->join('users as users_u','users_u.id','user_tasks.request_by')

        ->where( 'assign_tasks.id',$id)
        ->select('user_tasks.*','users_u.first_name','users_u.profilepic')->get();
        $assign_tasks = AssignTasks::find($id);
        

            return view('TaskMigrate.edit',compact('user_tasks','assign_tasks','task_details',$id));      
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TaskMigrate  $taskMigrate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TaskMigrate $taskMigrate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TaskMigrate  $taskMigrate
     * @return \Illuminate\Http\Response
     */
    public function destroy(TaskMigrate $taskMigrate)
    {
        //
    }
}
