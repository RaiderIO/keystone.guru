@extends('layouts.app')

@section('header-title', 'Create dungeon')

@section('content')
{!! Form::open(['route' => 'dungeon.store']) !!}
    Create a new dungeon!
{!! Form::submit('Submit', ['class' => 'btn btn-info']) !!}

{!! Form::close() !!}
@endsection
