<?php

namespace App\Http\Controllers\student;
use DB;
use App\quiz;
use App\User;
use App\constants;
use App\course;
use App\chapter;
use App\coinsinout;
use App\questions;
use App\enrollment;
use App\coursetask;
use App\AssignTasks;
use App\quizstatuses;
use App\chapterstatuses;
use App\online_quiz_statuses;
use App\online_quiz_questions;
use App\online_quiz_results;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;


class studentController extends Controller
{
    public function showDashboard()
    {
        $current_student = Auth::user();
        dd($current_student);
    }
/*##############################################################################################*/ 
    /*enroll a student to a particular course*/
    
    public function enroll(Request $request, $id)
    {
        
        $course_id = hd($id);
        // return $course_id;
        // if(Auth::user()->status == 1)
        // {
            $status = enrollment::where('status', 1)->where('student_id',Auth::user()->id)->count();
            // return $status;
            if ($status == 0)
            {
                $enrollment = Auth::user()->enrollment()->where('course_id', $course_id)->get()->count();

                if ($enrollment == 0)  
                {
                    if(!isset($request->guide_id))
                        {
                            $course=course::find($course_id);
                            // return
                            $teachers = User::
                            where('institutes_id',User::find($course->user_id)->institutes_id)        
                            ->where('role_id','<=',5) 
                            ->select('id','first_name','last_name','email')
                            ->get();
                            
                            return view('course.enroll')->with('course', $course)->with('teachers',  $teachers );
                        }
                        else
                        {
                            // return
                            $coinsleft=DB::table('coinsinouts')->where('user_id',Auth::user()->id)->sum('coins');
                            $coinsrequired=course::find($course_id)->cost;
                            if($coinsrequired<=$coinsleft)
                            {

                                $enrollment = new  enrollment();
                                $enrollment->student_id = Auth::user()->id;
                                $enrollment->course_id = $course_id;
                                $enrollment->status = 1;
                                $enrollment->guide_id = $request->guide_id;
                                $enrollment->reviewer_id = $request->reviewer_id;
                                // return $enrollment;
                                $enrollment->save();
                                // return enrollment::where('student_id', Auth::user()->id)
                                // ->where('course_id' , $course_id)->first()->id;
                                // DB::table('coinsinouts')->insert([
                                // 'user_id'=> Auth::user()->id,
                                // 'nature_id'=>2,
                                // 'enrollment_id'=>enrollment::where('student_id', Auth::user()->id)
                                // ->where('course_id' , $course_id)->first()->id,
                                // 'coins'=>-$coinsrequired,
                                // "created_at" =>  \Carbon\Carbon::now(), # \Datetime()
                                // "updated_at" => \Carbon\Carbon::now(),  # \Datetime()
                                // ]); 
                                $coinsinout = new  coinsinout();
                                
                                $coinsinout->nature_id = 2;
                                $coinsinout->user_id = Auth::user()->id;
                                $coinsinout->enrollment_id = enrollment::where('student_id', Auth::user()->id)
                                ->where('course_id' , $course_id)->first()->id;
                                $coinsinout->coins = -$coinsrequired;
                                // return $coinsinout;
                                $coinsinout->save();
                                // $coins =100;

                                return redirect()->route('public.home')->with([
                                    'title' => 'Enrollment success',
                                    'message' => 'You have been enrolled to the course',
                                    'type' => 'success',
                                ]);   
                            }
                            else
                            {
                                return redirect()->route('public.home')
                                ->with('alert','You dont have sufficient coins to enroll this course');
                             
                            }
                        }    
                    }
                else
                {
                    return redirect()->back()->with('alert','You alredy enroll this course.');
                }
            } 
            else 
            {
                return redirect()->back()->with('alert', 'You have already enrolled for One Course,Please complete one to open another Course!!!');
            }
        // }
        // else
        // {
        //     return redirect()->back()->with('alert','Your account is not activated or Blocked.You are not permited to enroll course ,Please contact Admin.
        //      e-mail : info@ameyem.com or Arun Babu : 8800197778, Venkat : 9848041175, Office : 0866-2470778');

        // }
    }

/*##############################################################################################*/ 
    /*show course library */
    public function courseLibrary()
    {
        $courses = course::all();
        $enrollments = enrollment::where('student_id',Auth::user()->id)->get();
        foreach($courses as $course){
            foreach($enrollments as $enrollment){
                if($course->id==$enrollment->course_id){
                    $course->enrolled=true;
                }
                
            }
        }
        return view('course.library')->with('courses', $courses);
    }

/*##############################################################################################*/ 
    /*show current students enrollments*/
    public function showEnrollments()
    {

    }
/*##############################################################################################*/ 
    /*show the contents of the courses associated with the student */
    public function studentCourses()
    {
        $studentData = Auth::user()->load('enrollment.course');
        /*return $studentData;*/
        return view('student.courses')->with('studentData',$studentData);
    }
/*##############################################################################################*/ 
  /*view a particular course*/
    public function viewCourse($id)
    {
        $id = hd($id);
        
        $course =  course::withCount('chapter')->with('chapter')->where('id',$id)->first();         
        // return
         $chids=array_column($course->chapter->toArray(),'id');
        //  return
        $tasks=coursetask::whereIn('chapter_id',$chids)->get();
        $quizs = quiz::whereIn('chapter_id',$chids)->get();
        // return
        $chpterstatuses=DB::table('chapterstatuses')->whereIn('chapter_id',$chids)->get();
        // return $chpterstatuses;
        $task_statustable=[];
        $zerostatus=false;
        if (count($chpterstatuses)>0){
            // $status_chid=array_column($chpterstatuses,'status');
            foreach($chpterstatuses as $chpterstatus)
            {               
                if($chpterstatus->status==0)
                {   $zerostatus=true;       
                        
                }
            }
            // return (int)$zerostatus;
            if(!$zerostatus ){
                // return (int)$zerostatus;
                // DB::table('chapterstatuses')->where('chapter_id',$chids[count($chpterstatuses)] )->where('user_id',Auth::user()->id)
                // ->update(['status' => '0']); 
                if(count($chpterstatuses)<count($chids)){
                    chapterstatuses::create(['status' => '0','chapter_id'=>$chids[count($chpterstatuses)],'user_id'=>Auth::user()->id]); 
            
                }
            }

        }else{
           chapterstatuses::create(['status' => '0','chapter_id'=>$chids[0],'user_id'=>Auth::user()->id]); 
        }
        // return $task_statustable;
        $firstzero=false;
        $quiztobeopened=false;
        $user_credits=0;
        // return $course->chapter;
        // return $chpterstatuses;
        foreach ($course->chapter as $cch )
        {
            $cch->quiz=getTaskIds($cch->id,$quizs);
            $cch->tasks=getTaskIds($cch->id,$tasks);
            $cch->status= getChapterStatus($cch->id,$chpterstatuses);
            // return $cch;

            if (!$cch->status[0]&&!$firstzero){
                //return $cch;
                $task_statustable=AssignTasks::where('course_chapter_id',$cch->id)
                        ->where('user_id',Auth::user()->id)->whereIn('task_id',$cch->tasks)
                        ->select('status','course_chapter_id','user_credits')->get();

                        // return $task_statustable;
                        
                        if(count($task_statustable)>0){
                            $quiztobeopened=true;                            
                            foreach($task_statustable as $task_status)
                            {
                                if($task_status->status!='approved')
                                {
                                    $quiztobeopened=false;
                                }else{
                                    $user_credits += $task_status->user_credits;
                                }
                            }
                
                       }
                      
                       if(count($task_statustable)!=count($cch->tasks)) $quiztobeopened=false;
                       $firstzero=true;
                       
            }
            // $cch->status[2]= $cch->status[2]*constants::max_credits_each_chapter;
            //quiz_score multiplied with credits assigned for chapter
        }

        // return $course;
        
       
    //    return $user_credits;
    // return (string)$quiztobeopened;
        return view('student.course')->with('course',$course)
        ->with('quiztobeopened',$quiztobeopened)
        ->with('user_credits',$user_credits);

    }

/*##############################################################################################*/ 
    /*view Chapter by student*/
    public function  viewChapter($course_id,$id)
    {
       
        $id = hd($id);
        $course_id = hd($course_id);
        $chapter = chapter::where('id',$id)->with('course')->where('course_id',$course_id)->first();
        $tasks = coursetask::where('chapter_id',$id)
        ->join('admin_tasks','admin_tasks.id','coursetasks.task_id')
        ->select('admin_tasks.*','coursetasks.id as coursetask_id')
        ->get();
// return
    $chpterstatuses=DB::table('chapterstatuses')
    ->where('user_id',Auth::user()->id)
    ->where('chapter_id',$id)->select('status')->get();
    if(count($chpterstatuses)==0){

        // return ' notset';
        // DB::table('chapterstatuses')->create(['user_id'->Auth::user()=>id,'chapter_id'=>$id,'status'=>0]);
        chapterstatuses::create(['status' => '0','chapter_id'=>$id,'user_id'=>Auth::user()->id]);
        // return ' now set';
    }
       $taskstatuses = AssignTasks::where('user_id',Auth::user()->id)
       ->where('course_chapter_id',$id)
       ->select('id','task_id','status','user_credits') 
       ->get();
         
       foreach($taskstatuses as $taskstatus)
       {
           foreach($tasks as $task)
           {
                if($taskstatus->task_id==$task->id)
                {
                    $task->status=$taskstatus->status;
                    $task->earned_credits=$taskstatus->user_credits;
                    $task->assigntask_id=$taskstatus->id;
                    $task->task_id=$taskstatus->task_id;

                } 
                
            }
       }
    // return $chapter->tasks;
        return view('course.viewChapter')->with('chapter',$chapter)->with('tasks',$tasks);
    }


/*##############################################################################################*/ 
    /* Assign task by student himself*/
    public function assignTask($coursetask_id)
    {
        $coursetask_id=hd($coursetask_id); 
        // return 
        $ctaskdetails=coursetask::where('coursetasks.id',$coursetask_id)
        ->join('chapters','chapters.id','coursetasks.chapter_id')
        ->join('enrollments','enrollments.course_id','chapters.course_id')
        ->select('coursetasks.*','enrollments.guide_id as priority_guide_id',
        'enrollments.reviewer_id as priority_reviewer_id')->first();
        
        try 
            {
                $record = [
                    'task_id' => $ctaskdetails->task_id,
                    'assigned_by_userid' => Auth::user()->id,
                    'user_id' =>Auth::user()->id,
                    'guide_id' => $ctaskdetails->priority_guide_id,
                    'reviewer_id' => $ctaskdetails->priority_reviewer_id,
                    'course_chapter_id'=>$ctaskdetails->chapter_id,
                    'status'=>'initiated',
                    'target_at' => Carbon::now('Asia/Kolkata')->addDays($ctaskdetails->time_required),

                ];    
                AssignTasks::create( $record );
                // return $record;
                
            }
            catch (Exception $e)
            {
                render($e);

                return false;
            }

           $assign_task_id= AssignTasks::where( 'task_id', $record['task_id'])->where( 'user_id', $record['user_id'])->select('id')->first();

            // return ["Successfully assigned",   $record];
           return redirect()-> route('UserTasks.edit',$assign_task_id);
    }

  
/*##############################################################################################*/ 
    /*handle submitted quiz data*/
    /**
     * @param Request $request
     * @return mixed
     */
    public function postQuiz(Request $request){
        
        $score = null;
        $total = null;
        // return $request;
        $quiz_data = $request->except('_token','chapter_id');
        $chapter_id = hd($request->chapter_id);
        // return
        $questions = chapter::find($chapter_id)->quiz->question()->get();
        // return $questions;
        $quizstatuses = quizstatuses::whereIn('question_id',array_column($questions->toArray(),'id'))
        ->where('user_id',Auth::user()->id)->get();

         if(count($quizstatuses)==0)    {

            foreach ($questions as $question)
            {
                foreach ($quiz_data as $key => $value)
                {
                    if($question->id == hd($key))
                    {
                        $total = $total+1; 
                        $quizstatuses = new quizstatuses();
                        $quizstatuses->question_id = $question->id;
                        $quizstatuses->user_id = Auth::user()->id;
                        $quizstatuses->answer = $value;    
                                    

                        if($question->answer == $value)
                        {
                            $score+=1;
                            $question->answerd = true;
                            $quizstatuses->result = 'true';
                        }
                        else
                        {
                            $question->answerd = false;
                            $quizstatuses->result = 'false';
                        }
                        $question->user_answer = $value;
                        
                        $quizstatuses->save();

                    }
                }
            }
            /*checking wether the score is above 80%*/
            $quizscore=($score/$total)*100;
                if( $quizscore>= constants::min_score_for_pass ){$status ='passed'; }
                else{ $status = 'failed'; }

            /*quiz results*/
            $results = [
                'total' => $total,
                'score' =>$score,
                'status'=>$status
            ];
            // // return
            $task_credits = AssignTasks::where('user_id',Auth::user()->id)
            ->where('course_chapter_id',$chapter_id)            
            ->sum('user_credits');

            /* inserting status 1 into chapterstatuses table */
            DB::table('chapterstatuses')->where('chapter_id',$chapter_id)->where('user_id',Auth::user()->id)
            ->update(['status' => 1,'quiz_score'=>$quizscore,'task_credits'=>$task_credits]); 
            // return
            $course_id=chapter::where('id',$chapter_id)->select('course_id')->first();
            $chids=chapter::where('course_id',$course_id->course_id)->select('id')->get()->toArray();
            // return $chids;
            //  [$chapter_id,$course_id,$chids ];
            $ch_statuses=chapterstatuses::whereIn('chapter_id',$chids)->where('user_id',Auth::user()->id)->select('id')->get();

            if(count($chids)==count($ch_statuses)){
                // return
                // $course_id;
                return redirect()->route('feedback',he($course_id->course_id));              
                
            }
        
         }   else{

            return  "You have already submitted quiz...";
         }   


        
        return view('quiz.review')->with(['questions'=>$questions,'results'=>collect($results)]);
    }
/*####################################################################################################################################*/ 

public function postFeedback(Request $request,$id){
    // return 
    // $request;
    $course=course::where('courses.id',hd($id))
                ->join('enrollments','enrollments.course_id','courses.id')
                ->join('users as users_g','users_g.id','enrollments.guide_id')
                ->join('users as users_r','users_r.id','enrollments.reviewer_id')
                ->select('courses.*','enrollments.student_id','enrollments.guide_id','enrollments.reviewer_id',
                'users_g.first_name as guide_name','users_r.first_name as reviewer_name')
                ->first();
    
    if(!isset($request->_token)){       
        
                return view('course.feedback')->with('course',$course);
    }
        $this->validate($request, [
            'guide_rating' =>'required',
            'reviewer_rating' => 'required',
            'course_rating' =>'required',
            'comment' => 'required',
            
        ]);
        enrollment::where('course_id',hd($id))->where('student_id',Auth::user()->id)->update($request->except(['_token']));
        
        $chids=chapter::where('course_id',hd($id))
            ->select('id')->get()->toArray();
     $ch_statuses=chapterstatuses::whereIn('chapter_id',$chids)
            ->select('*')->get();

        $course_credits=$ch_statuses->sum('task_credits')+constants::max_credits_each_chapter*($ch_statuses->sum('quiz_score')/100);
        
        // $bonus_credits =array_column($ch_statuses->toArray(),'created_at');
        $hours4completion = (new Carbon($ch_statuses->first()->created_at))
        ->diffInHours(new Carbon($ch_statuses->last()->created_at));
            $days=$hours4completion/24.0;
            if($days<1) $days=1;
        $bonus_credits= $course_credits*constants::perc_cred_bonus_on_coursecompletion/$days;
        


        enrollment::where('course_id',$course->id)
        ->where('student_id',Auth::user()->id)
        ->update(['status'=>2,'course_credits'=>$course_credits,'bonus_credits'=>$bonus_credits]);
    
    // return
        $all_task_credits=AssignTasks::where('assign_tasks.user_id', Auth::id())
        ->whereIn('course_chapter_id',$chids)
        ->join('admin_tasks','assign_tasks.task_id','admin_tasks.id')
        ->select('assign_tasks.*','admin_tasks.usercredits','admin_tasks.guidecredits','admin_tasks.reviewercredits')
        ->get();
        // return
        // $course=course::findOrFail(hd($id));
         
        $usercoins=$course->cost*constants::share_student*($course_credits/($all_task_credits->sum('usercredits')
        +constants::max_credits_each_chapter*count($chids)));
        $guidecoins=$course->cost*constants::share_guide*($all_task_credits->sum('guide_credits')/$all_task_credits->sum('guidecredits'));
        $reviewercoins=$course->cost*constants::share_reviewer*($all_task_credits->sum('reviewer_credits')/$all_task_credits->sum('reviewercredits'));
        
        $course_owner_coins=$course->cost*constants::share_course_creator;
        $company_coins=$course->cost-($usercoins+$guidecoins+$reviewercoins+$course_owner_coins);
        // return 

        
       $coinsinout_array= array(
        [$course->student_id,6,$usercoins,],
        [$course->guide_id,4,$guidecoins,],
        [$course->reviewer_id,5,$reviewercoins,],
        [$course->user_id,3,$course_owner_coins,],
        [2,7,$company_coins,],
       );
       $enroll_id=enrollment::where('student_id', Auth::user()->id)
       ->where('course_id' , $course->id)->first()->id;
        // DB::table('coinsinouts')->insert([
        //     'user_id'=> Auth::user()->id,
        //     'nature_id'=>2,
        //     'enrollment_id'=>$enrollment->id(),
        //     'coins'=>-$coinsrequired,
        //     "created_at" =>  \Carbon\Carbon::now(), # \Datetime()
        //     "updated_at" => \Carbon\Carbon::now(),  # \Datetime()
        //     ]); 
        foreach($coinsinout_array as $coinsinout_){
            $coinsinout = new  coinsinout();                
            $coinsinout->nature_id = $coinsinout_[1];
            $coinsinout->user_id = $coinsinout_[0];
            $coinsinout->enrollment_id = $enroll_id;
            $coinsinout->coins = $coinsinout_[2];
            $coinsinout->save();
        }

        // const share_course_creator =0.2;
        // const share_guide =0.4;
        // const share_reviewer =0.1;
        // const share_student =0.2;
        // const share_company =0.1;
        return 
        redirect('home')->with('success', trans('socials.feedbackSuccess'));


    }


/*##############################################################################################*/ 
  /*view quiz*/
    public function viewQuiz($id)
    {
        $id = hd($id);
        $quiz_data = chapter::find($id)
        // ->select('chapters.*')
        ->quiz
        ->where('chapter_id',$id)
        ->with('question')
        ->first()
        ;
        
        // return $time4quiz=count($quiz_data)*45; //secons

        // return $course_id;
        return view('quiz.viewQuiz')->with('quiz_data',$quiz_data);
    }

/*##############################################################################################*/  

    public function viewQuizResult(Request $request ){
        $score = null;
        $total = null;
        // return
        $chapter_id = hd($request->id);
        $questions = chapter::find($chapter_id)->quiz->question()->get();
        // return $questions;
        /*
            Evaluate each question attended
            Check whether the answer is correct
        */
        foreach ($questions as $question){
            $quiz_data = quizstatuses::where('question_id',$question->id)->get();
            // return $quiz_data;
            foreach ($quiz_data as $result){
                if($question->id == $result->question_id){
                    $total = $total+1;
                    if($question->answer == $result->answer){
                        $score+=1;
                        $question->answerd = true;
                    }
                    else{
                        $question->answerd = false;
                    }
                    $question->user_answer = $result->answer;
                }
            }
        }
        /* inserting results into quizstatuses table */

        /*checking wether the score is above 80%*/
        if(($score/$total)*100 >= constants::min_score_for_pass ){
            $status ='passed';
        }
        else{
            $status = 'failed';
        }

        /*quiz results*/
        $results = [
            'total' => $total, 
            'score' =>$score,
            'status'=>$status
        ];

        
        return view('quiz.viewQuizResult')->with(['questions'=>$questions,'results'=>collect($results)]);
    }

/*############################################################################################################################################### */

    public function quizzes()
    {
        $quizzes =  DB::table('online_quizzes')
        ->select('online_quizzes.*')->get();
        // return $quizzes;
        return view('online_quiz.online',compact('quizzes'));
    }

/*############################################################################################################################################### */

    public function quizAttempt($id)
    {
        $quiz_id = hd($id);
        // return $quiz_id;
        $all_questions = DB::table('online_quiz_questions')
        ->join('online_quizzes','online_quiz_questions.online_quiz_id','=','online_quizzes.id')
        ->where('online_quiz_questions.online_quiz_id',$quiz_id)
        ->select('online_quiz_questions.id')->get();
       
        $first_value = $all_questions[0]->id;

        $single_questions = DB::table('online_quiz_questions')
        ->join('online_quizzes','online_quiz_questions.online_quiz_id','=','online_quizzes.id')
        ->where('online_quiz_questions.id',$first_value)
        ->select('online_quiz_questions.*')->get();
        $question_id=0;
        return view('online_quiz.quizAttempt')->with('quiz_id',$quiz_id)->with('question',$single_questions[0])->with('all_questions',$all_questions)
        ->with('question_id',$question_id);

    }

/*############################################################################################################################################### */

    public function search_question($id,$q_id)
    {
        $quiz_id = hd($id);
        $question_id = hd($q_id);

        $all_questions = DB::table('online_quiz_questions')
        ->join('online_quizzes','online_quiz_questions.online_quiz_id','=','online_quizzes.id')
        ->where('online_quiz_questions.online_quiz_id',$quiz_id)
        ->select('online_quiz_questions.*')->get();
      $single_questions = DB::table('online_quiz_questions')
        ->where('online_quiz_questions.id',$all_questions[$question_id]->id)
        ->select('online_quiz_questions.*')->get();


        return view('online_quiz.quizAttempt')->with('quiz_id',$quiz_id)->with('question',$single_questions[0])->with('all_questions',$all_questions)
        ->with('question_id',$question_id);
   
    }

/*############################################################################################################################################### */

    public function save_answer($id,$q_id,Request $request)
    {
        $quiz_id = hd($id);
        $question_id = hd($q_id);
        // return $quiz_id;

        $all_questions = DB::table('online_quiz_questions')
        ->join('online_quizzes','online_quiz_questions.online_quiz_id','=','online_quizzes.id')
        ->where('online_quiz_questions.online_quiz_id',$quiz_id)
        ->select('online_quiz_questions.id')->get();
        $qid=$all_questions[$question_id]->id;


        $questions = DB::table('online_quiz_questions')
        ->join('online_quizzes','online_quiz_questions.online_quiz_id','=','online_quizzes.id')
        ->where('online_quiz_questions.id',$qid)
        ->select('online_quiz_questions.*')->get();

        $quiz_data = $request->except('_token');
        // return $quiz_data;

        $question_status = DB::table('online_quiz_statuses')
        ->where('online_quiz_statuses.online_quiz_question_id',$qid)
        ->where('online_quiz_statuses.user_id', Auth::user()->id)->count();
        
        // return $question_status;

        if($question_status == 0)
        {
            foreach ($questions as $question)
            {
                foreach ($quiz_data as $key => $value)
                {
                    if($qid == hd($key))
                    {
    
                        $save = new online_quiz_statuses();
                        $save->online_quiz_question_id = $qid;
                        $save->user_id = Auth::user()->id;
                        $save->user_answer = $value;
                        
                        if($question->answer == $value)
                        {
                            $save->result = 'true';
                        }
                        else
                        {
                            $save->result = 'false';
                        }
                        
                        // return $save;
                        $save->save();
                    }
                }
            }
        }

            
        
        if($all_questions->count()-1 == $question_id)
        {
            return redirect()->route('viewResult',[he($quiz_id)])->with('success','Quiz Submitted successfully');
        }
        else
        {
             $ques_id = ++$question_id;
            return redirect()->route('search_question',[$id,he($ques_id)])->with('success','Answer saved successfully');
        }
          
        
            
        
    }

/*######################################################################################################################################################*/

    public function viewResult($id,Request $request){
        $score = null;
        $total = null;
        // return
        $quiz_id = hd($request->id);
        $questions = online_quiz_questions::where('online_quiz_id',$quiz_id)->get();
        // return $questions;
        $total_questions = $questions->count();
        /*
            Evaluate each question attended
            Check whether the answer is correct
        */
        foreach ($questions as $question){
            $quiz_data = online_quiz_statuses::where('online_quiz_question_id',$question->id)->where('user_id',Auth::user()->id)->get();
            // return $quiz_data;
            foreach ($quiz_data as $result){
                if($question->id == $result->online_quiz_question_id){
                    $total = $total+1;
                    if($question->answer == $result->user_answer){
                        $score+=1;
                        $question->answerd = true;
                    }
                    else{
                        $question->answerd = false;
                    }
                    $question->user_answer = $result->user_answer;
                }
            }
        }
        /* inserting results into quizstatuses table */

        /*checking wether the score is above 80%*/
        if(($score/$total)*100 >= constants::min_score_for_pass ){
            $status ='passed';
        }
        else{
            $status = 'failed';
        }


        if($score == 0)
        {
            $score = 0;
        }
        /*quiz results*/
        $results = [
            'total' => $total, 
            'score' =>$score,
            'status'=>$status
        ];
        if($score == 0)
        {
            $score = 0;
        }
        $enquiry = DB::table('online_quiz_results')->where('user_id',Auth::user()->id)->where('quiz_id',$quiz_id)->count();
        // return $enquiry;
        //store all quiz results in online_quiz_results table.
        if($enquiry == 0)
        {
            $quiz_results = new online_quiz_results();
            $quiz_results->user_id = Auth::user()->id;
            $quiz_results->quiz_id = $quiz_id;
            $quiz_results->score = $score;
            $quiz_results->total = $total_questions;
            $quiz_results->save(); 
        }

        return view('online_quiz.viewResult')->with(['questions'=>$questions,'results'=>collect($results)]);
    
    }


}

