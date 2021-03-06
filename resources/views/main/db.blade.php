<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Autodrive</title>

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="content">
                <div class="title m-b-md">
                    Autodrive
                </div>
                <div>
                    {{-- <a href="{{ route('db.delete') }}">Delete</a>
                    <a href="{{ route('db.create') }}">Create</a>
                    <a href="{{ route('db.count') }}">Count</a>
                    <a href="{{ route('db.sessions') }}">Sessions</a>
                    <a href="{{ route('db.migrate') }}">Migrate</a>
                    <a href="{{ route('db.list') }}">List</a>
                    <a href="{{ route('admin.data.seed') }}">Seed</a> --}}
                </div>
                @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
                @endif
                @if (session('tables'))
                <div class="alert alert-success">
                    @foreach (session('tables') as $name)
                    <p>{{ $name }}</p>
                    @endforeach
                </div>
                @endif
                @if (session('error'))
                <div class="alert alert-success">
                    <code>{{ session('error') }}</code>
                </div>
                @endif
            </div>
        </div>
    </body>
</html>
