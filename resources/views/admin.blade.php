@extends('layouts.app')

@section('content')
    <div id="app" v-cloak>
        <router-view></router-view>
    </div>

    <script src="{{ mix('js/admin.js') }}"></script>
@endsection
