@extends('layouts.master')

@section('heading')
    {{ __('Clear Data') }}
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Warning!</strong> Clearing data is irreversible. Please make sure you have a backup.
            </div>
            
            <form action="{{ route('data.clear.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="data_type">Select data type to clear:</label>
                    <select name="data_type" id="data_type" class="form-control">
                        <option value="clients">Clients</option>
                        <option value="projects">Projects</option>
                        <option value="tasks">Tasks</option>
                        <option value="leads">Leads</option>
                        <option value="all">All Data</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="confirm">Type "CONFIRM" to proceed:</label>
                    <input type="text" name="confirm" id="confirm" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-danger">Clear Data</button>
            </form>
        </div>
    </div>
@stop
