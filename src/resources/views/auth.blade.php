@extends('sso::layout')

@section('title', __( 'Sistem iLogin'))
@section('code', '401')
@section('message', __( 'Pengesahan sedang dijalankan.'))
@section('script')
    <script>
        setTimeout(() => {
            window.location.href = "{{ route('sso.verify') }}";
        }, 3000);
    </script>
@endsection
