@extends('layouts.master')

@section('heading')
    {{ __('Import Data') }}
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('data.import.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="data_type">Select data type to import:</label>
                    <select name="data_type" id="data_type" class="form-control">
                        <option value="clients">Clients</option>
                        <option value="projects">Projects</option>
                        <option value="tasks">Tasks</option>
                        <option value="leads">Leads</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="import_file">Select CSV file to import:</label>
                    <input type="file" name="import_file" id="import_file" class="form-control-file" required>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="has_headers" id="has_headers" class="form-check-input" checked>
                    <label for="has_headers" class="form-check-label">File has headers</label>
                </div>
                
                <button type="submit" class="btn btn-primary mt-3">Import Data</button>
            </form>
        </div>
    </div>
@stop
