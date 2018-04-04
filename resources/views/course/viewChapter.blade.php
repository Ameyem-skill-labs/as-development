@extends('layouts.app')
 <link rel="stylesheet" href="/css/card.css">
<style>
    #chapter_data{
        background: white;
    }
    .label-chapter{
        background: #141204;
        font-size:16px !important;
        font-weight: 400px;
    }
    #notes{
        overflow-y: auto;
    }

    .cover{
        padding:100px;
        background: url({{route('coverImage',['id'=>$chapter->course->cover])}});
        margin-bottom:20px;
        background-size: contain;
    }

    .cover h1{
        background: linear-gradient(rgb(243, 243, 243),rgba(243, 243, 243, 0.55));
        font-weight: 300;
        padding:10px;
    }

    .cover h3{
        background: #f4f4f4;
    }

    .navbar{
        margin-bottom:0px !important;
        border-top: 0px;
    }
    .btn-red{
        background: #432534;
        color:#EFD6AC;
    }
</style>
@section('content')
   <div class="container" id="chapter_data">
       <h2 class="text-center">{{$chapter->name}}</h2>
        <!-- <a  class="btn btn-primary"
                        href="{{ route('manageCourse',['id'=>he($chapter->course_id)]) }}">Back</a> -->
                        <hr>
        <div class="container text-center">
        <h2 >Tasks to be completed</h2>
        <?php $tcount = 0 ?>
            @foreach($tasks as $task)

            <div class="card card-hover bg-danger  col-xs-12 col-sm-6 col-md-4">
            
                <div class="card-body">
                <h3 class="card-title">Task {{$tcount+=1}}: {{$task->worktitle}}</h3>
                <p class="card-text">Task id: {{$task->coursetask_id}}</p>
                <table class="table table-dark table-hover ">
                <tr>
                    <td>
                    <p class="card-text">Guide: {{$task->gname}}</p>
                    </td>
                    
                    <td>
                    {{$task->subject}}
                    </td>
                    <td>
                    <p class="card-text">{{$task->worknature}}</p>
                    </td>
                
                <tr>
                </table>
                {{--  <a href="#" class="card-link">Card link</a>
                <a href="#" class="card-link">Another link</a>  --}}
                <div class="card-footer">
                <p class="card-text">Use: {{$task->whatinitforme}}</p>
                <p class="label label-chapter"> Max Credits: {{$task->usercredits}}</p>
                {{--  <p class="label label-chapter">{{$task->guidecredits}}</p>
                <p class="label label-chapter">{{ $task->reviewercredits}}</p>  --}}
                {{--  <p class="card-text">Guide: {{$task->gname}}</p>  --}}
                </div>
                @if(!isset ( $task->status))
                <a  class="btn btn-warning"                
                    href="{{ route('assigntask',['coursetask_id'=>he($task->coursetask_id)]) }}">Attempt</a>
                
                @else
                    @if($task->status!="approved")
                    <a class="btn btn-primary" href="{{ route('UserTasks.edit',$task->assigntask_id) }}">View Work</a>
                    @else
                    <p class="label label-success">Completed</p>
                    @endif
                @endif  
                </div>

            </div>

            @endforeach
        </div>
       @if(!empty($chapter->video))
       <hr>
       
       <h2 class="text-center">Video tutorial</h2>
       <hr>

       <div class="row">
           {{--video lesson --}}
               <video  controls class="col-md-10 col-md-offset-1" controls preload="auto" data-setup="{}">
                   <source src=" {{route('serveVideo',['id'=>$chapter->video]) }}" type="video/mp4">
                   Your browser does not support the video tag.
               </video>
       </div>
       @endif
       @if(!empty($chapter->pdf))
       <div class="row">
           {{--Chapter Ebooks--}}
           <hr>
           <h2 class="text-center">Ebooks</h2>
           <hr>
           <div>
               <a href="{{route('serveEbook',['id'=>$chapter->pdf])}}" target="_blank" class="col-md-8 col-md-offset-2 button btn btn-red">Read Ebook</a>
           </div>
       </div>
       @endif

       {{--Notes of the chapter--}}
       <hr>
       <h2 class="text-center">Notes</h2>
       <hr>
       <div id="notes">
           {!! $chapter->notes !!}
       </div>
   </div>
@endsection



