@extends('sso::layout')

@section('title', __( 'Sistem iLogin'))
@section('message', __( 'Pengesahan sedang dijalankan.'))
@section('script')
    <script>
        setTimeout(() => {
            window.location.href = "{{ route('sso.verify') }}";
        }, 1000);
    </script>
@endsection
