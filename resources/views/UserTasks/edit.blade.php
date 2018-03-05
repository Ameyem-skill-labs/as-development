@extends('layouts.app')
@section('content')

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script type="text/javascript">
    $(function () {
        $("#role").change(function () {
            if ($(this).val() == "drop" || "approved") {
                $("#bb").show();
            } else {
                $("#bb").hide();
            }
        });
    });
</script>



<div class="row">
    <div class="col-lg-12 margin-tb">
        <div class="pull-left">
            <h2>Send Your Task</h2>
        </div>
        <div class="pull-right">
              
        </div>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <tr>
            <th>Assign Task Id</th> 
            <th>Request</th>
            <th>Message</th>
            <th>Files</th>
            <th>Date</th>
            {{--  <th>Obtained Marks</th>  --}}
                        
        </tr>
        @foreach ($user_tasks as $task)
        <tr>
            <td>{{ $task->assigntask_id }}</td> 
            <td>{{ $task->request_for }}</td>
            <td>{{ $task->message }}</td>
            @if ($task->uploads)
            <td><a class="btn btn-info btn-xs" href="/download/{{ $task->uploads}}" download="{{ $task->uploads}}">File Links</a></td>
            @else
            <td>Nill</td>
            @endif
            <td>{{ $task->created_at }}</td>
            {{--  <td>{{ $task->obtained_marks }}</td>  --}}
        </tr>
        
         @endforeach
         
    </table>

    
@if($assign_tasks->status === 'drop')
                        <h1>Task Dropped</h1>

                        @elseif($assign_tasks->status === 'approved')
                        <h1>Task Approved</h1>


                        @else

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-sm-4"></div>
                                    <div class="col-sm-4" style="background-color:lavender;">

                                    {!! Form::open(array('route' => 'UserTasks.store','method' => 'POST','files' => true)) !!}


                                    <div class="col-xs-12 col-sm-12 col-md-12" style="display:none">
                                            <div class="form-group">
                                                <strong>Assigned Task Id:</strong>
                                                {!! Form::text('assigntask_id', $assign_tasks->id) !!}
                                                
                                        </div>
                                    </div>


                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <strong>Request For:</strong>
                                                {!! Form::select('request_for', [
                                                    '1' => ['review' => 'review']],
                                                    array('class' => 'form-control')) !!}
                                            </div>
                                    </div>
                                    

                                    <div class="col-xs-12 col-sm-12 col-md-12" style="display:none">
                                            <div class="form-group">
                                                <strong>Request By :</strong>
                                                {!! Form::text('request_by', Auth::user()->id) !!}
                                            </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="form-group">
                                                <strong>Message :</strong>
                                                {!! Form::textarea('message', null, array('placeholder' => 'Message ','class' => 'form-control')) !!}
                                            </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <strong>Upload:</strong>
                                                {!! Form::file('uploads') !!}
                                        </div>
                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>

                                </div>
                               
                            @endif
                            
</div>





@endsection