@extends('layouts.master')

@section('heading')
    {{ __('Clear Data') }}
@stop

@section('content')
    <div class="card">
        <div class="card-body text-center">
            <div class="alert alert-warning mb-4">
                <strong>Warning!</strong> Clearing all data is irreversible. Please make sure you have a backup.
            </div>
            
            <form action="{{ route('data.clear.post') }}" method="POST">
                @csrf
                <input type="hidden" name="data_type" value="all">
                
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fa fa-trash mr-2"></i>{{ __('Clear All Data') }}
                </button>
            </form>
        </div>
    </div>
@stop
