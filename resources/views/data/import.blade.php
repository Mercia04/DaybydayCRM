@extends('layouts.master')

@section('heading')
    {{ __('Import Data') }}
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('data.import.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="card-title">{{ __('Import Multiple Files') }}</h5>
                        <p class="text-muted">{{ __('Please select the three required files to import.') }}</p>
                    </div>
                </div>

                <!-- Projets et Clients -->
                <div class="form-group mb-4">
                    <label for="import_file">{{ __('Fichier Projets et Clients (CSV)') }}</label>
                    <input type="file" name="import_file" id="import_file" class="form-control" accept=".csv,.txt" required>
                    <small class="form-text text-muted">
                        {{ __('Format attendu: project_title,client_name') }}
                    </small>
                </div>

                <!-- Tâches -->
                <div class="form-group mb-4">
                    <label for="tasks_file">{{ __('Fichier Tâches (CSV)') }}</label>
                    <input type="file" name="tasks_file" id="tasks_file" class="form-control" accept=".csv,.txt" required>
                    <small class="form-text text-muted">
                        {{ __('Format attendu: project_title,task_title') }}
                    </small>
                </div>

                <!-- Leads et Offres/Factures -->
                <div class="form-group mb-4">
                    <label for="leads_file">{{ __('Fichier Leads et Offres/Factures (CSV)') }}</label>
                    <input type="file" name="leads_file" id="leads_file" class="form-control" accept=".csv,.txt" required>
                    <small class="form-text text-muted">
                        {{ __('Format attendu: client_name,lead_title,type,produit,prix,quantite') }}
                    </small>
                </div>

                @if(session('flash_message'))
                    <div class="alert alert-{{ strpos(session('flash_message'), 'Erreur') !== false ? 'danger' : 'success' }} mb-4">
                        {{ session('flash_message') }}
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-upload mr-2"></i>{{ __('Importer les données') }}
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        {{ __('Annuler') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop
