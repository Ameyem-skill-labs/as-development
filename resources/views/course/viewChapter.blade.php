@extends('layouts.tutapp')
<style>
    #chapter_data{
        background: white;
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
        <a  class="btn btn-primary"
                        href="{{ route('manageCourse',['id'=>he($chapter->course_id)]) }}">Back</a>
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



