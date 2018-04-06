@extends('layouts.app')

<style>
    #course-wrapper {
        background: white;
        border: 0.5px solid #dfdfdf;
    }
    .alert-red{
        background: #e55469;
        color:white;
    }
</style>

@section('content')
<body>

    {{--rich text editor scripts & styles--}}
    {{--  <link href="/css/summernote.css" rel="stylesheet">
    <script src="/js/summernote.js"></script>  --}}

    <div class="container" id="course-wrapper">
        <span class="alert alert-red">- Edit chapter -</span>
        <hr>

        {{--chapter form--}}
        <form action="{{ route('updateChapter',['id'=>he($chapter->id)]) }}" id="chapter_form" method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
            {{--chapter name--}}
            <div class="form-group">
                <label for="name">Name of chapter</label>
                <input type="text" name="name" class="form-control" value="{{$chapter->name}}">
            </div>


            

            {{--chapter notes editor--}}
            <div class="form-group">
                <label for="notes_editor">Chapter notes</label>
                <div id="notes_editor" class="form-control"></div>
            </div>

            {{--chapter Ebooks/presentation--}}
            <div class="form-group">
                <label for="pdfMaterial">Ebooks/presentation</label>
                <input type="file" name="pdfMaterial" class="form-control"  accept="application/pdf">
            </div>

            {{--chapter videos--}}
            <div class="form-group">
                <label for="video_tutorial">Video tutorial</label>
                <input type="file" name="video_tutorial"  class="form-control" accept="video/mp4" >
            </div>

            {{--submit button--}}
            <input type="submit" class="button btn btn-primary btn-lg" style="float:right">
        </form>
            
            @include('partials.summernotejs')
            

    </div>

    </body>
@endsection
{{--  <!-- Scripts -->

    <script src="/js/jquery.js"></script>
    <script src="/js/popper.js"></script>
    <script src="/js/bootstrap.js"></script>  --}}


