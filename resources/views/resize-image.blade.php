<!DOCTYPE html>
<html lang="en">
<head>
    <title>Resize</title>
    @include('layouts.header')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>
    <section style="padding-top:60px;">
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h5>Resize Image</h5>
                        </div>


                        <div class="form-group">
                            <input type="file" class="form-control" id="upload">
                            <div style="margin: 300px" id="image_show">
            
                            </div>
                            <input type="hidden" name="thumb" id="thumb">
                        </div>


                       {{--  <div class="card-bode">
                            <form action="{{ route('resize.image') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                    <input type="file" name="image" class="form-control" id="image">
                                <button type="submit" class="btn btn-success">Upload</button>
                            </form>
                            
                        </div>
                        <div style="display: block;margin-left: auto;margin-right: auto">
                            <img src="{{ $result ?? null }}" alt="Thumbnail">
                        </div> --}}

                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
<script src="/template/admin/js/main.js"></script>
</html>