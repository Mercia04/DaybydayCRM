<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function clearForm()
    {
        return view('data.clear');
    }

    public function clear(Request $request)
    {
        // shell_exec('php artisan migrate:fresh --seed');
        $output = shell_exec('cd ' . base_path() . ' && php artisan migrate:fresh --seed 2>&1');
        if(strpos($output,'SQLSTATE') !== false){
            Session::flash('flash_message', 'Error: ' . $output);
        }
        Session::flash('flash_message', 'Data cleared successfully!');
    
        return redirect()->back();
    }
    

    public function importForm()
    {
        return view('data.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt',
            'tasks_file' => 'required|file|mimes:csv,txt',
            'leads_file' => 'required|file|mimes:csv,txt'
        ]);

        $errors = [];
        $faker = \Faker\Factory::create('fr_FR');

        // Validation initiale des fichiers
        $files = [
            'import_file' => ['Projets et Clients', 2], // 2 colonnes requises : projet, client
            'tasks_file' => ['Tâches', 2], // 2 colonnes requises : projet, tâche
            'leads_file' => ['Leads et Offres/Factures', 6] // 6 colonnes requises : client, lead, type, produit, prix, quantité
        ];

        foreach ($files as $fileKey => $fileInfo) {
            $fileLabel = $fileInfo[0];
            $requiredColumns = $fileInfo[1];
            $file = $request->file($fileKey);
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));
            
            // Vérifier si le fichier est vide
            if (count($data) <= 1) {
                $errors[] = "Le fichier {$fileLabel} est vide";
                continue;
            }

            // Vérifier le nombre de colonnes dans chaque ligne
            array_shift($data); // Enlever l'en-tête
            foreach ($data as $index => $row) {
                if (count($row) < $requiredColumns) {
                    $errors[] = "Dans le fichier {$fileLabel}, ligne " . ($index + 2) . ": Il manque des colonnes. {$requiredColumns} colonnes sont requises.";
                }
            }

            // Vérifier les montants pour le fichier des leads
            if ($fileKey === 'leads_file') {
                foreach ($data as $index => $row) {
                    if (isset($row[4]) && is_numeric($row[4]) && $row[4] < 0) {
                        $errors[] = "Dans le fichier {$fileLabel}, ligne " . ($index + 2) . ": Le montant ne peut pas être négatif";
                    }
                    if (isset($row[5]) && is_numeric($row[5]) && $row[5] < 0) {
                        $errors[] = "Dans le fichier {$fileLabel}, ligne " . ($index + 2) . ": La quantité ne peut pas être négative";
                    }
                }
            }
        }

        // Si des erreurs ont été trouvées lors de la validation, on arrête tout
        if (!empty($errors)) {
            Session::flash('flash_message', "Erreurs de validation : " . implode(' | ', $errors));
            return redirect()->back();
        }

        try {
            \DB::beginTransaction();

            // Import des projets et clients
            $file = $request->file('import_file');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));
            array_shift($data); // Enlever l'en-tête

            $imported = 0;
            foreach ($data as $index => $row) {
                try {
                    // Trouver ou créer le client avec des données faker
                    $client = \App\Models\Client::firstOrCreate(
                        ['company_name' => $row[1]],
                        [
                            'company_name' => $row[1],
                            'vat' => $faker->numerify('FR##########'),
                            'address' => $faker->streetAddress(),
                            'zipcode' => $faker->postcode(),
                            'city' => $faker->city(),
                            'company_type' => $faker->randomElement(['Client', 'Prospect', 'Partenaire']),
                            'user_id' => auth()->id(),
                            'industry_id' => rand(1, 10),
                            'client_number' => $faker->numerify('####'),
                            'external_id' => uniqid('CLT_')
                        ]
                    );

                    // Créer un contact pour le client
                    \App\Models\Contact::firstOrCreate(
                        ['client_id' => $client->id],
                        [
                            'name' => $faker->name,
                            'email' => $faker->email,
                            'primary_number' => $faker->numerify('##########'),
                            'secondary_number' => $faker->numerify('##########'),
                            'is_primary' => true,
                            'external_id' => uniqid('CONT_')
                        ]
                    );

                    // Créer le projet
                    \App\Models\Project::create([
                        'title' => $row[0],
                        'client_id' => $client->id,
                        'status_id' => 1,
                        'user_created_id' => auth()->id(),
                        'user_assigned_id' => auth()->id(),
                        'external_id' => uniqid('PROJ_')
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    throw new \Exception("Dans le fichier Projets et Clients, ligne " . ($index + 2) . ": " . $e->getMessage());
                }
            }

            // Import des tâches
            $tasksFile = $request->file('tasks_file');
            $tasksPath = $tasksFile->getRealPath();
            $tasksData = array_map('str_getcsv', file($tasksPath));
            array_shift($tasksData); // Enlever l'en-tête

            $tasksImported = 0;
            foreach ($tasksData as $index => $row) {
                try {
                    // Trouver le projet correspondant
                    $project = \App\Models\Project::where('title', $row[0])->first();
                    
                    if ($project) {
                        // Créer la tâche
                        \App\Models\Task::create([
                            'title' => $row[1],
                            'project_id' => $project->id,
                            'client_id' => $project->client_id,
                            'status_id' => 1,
                            'user_created_id' => auth()->id(),
                            'user_assigned_id' => auth()->id(),
                            'external_id' => uniqid('TASK_'),
                            'description' => $faker->sentence(),
                            'deadline' => $faker->dateTimeBetween('now', '+2 months')
                        ]);

                        $tasksImported++;
                    } else {
                        throw new \Exception("Projet non trouvé: " . $row[0]);
                    }
                } catch (\Exception $e) {
                    throw new \Exception("Dans le fichier Tâches, ligne " . ($index + 2) . ": " . $e->getMessage());
                }
            }

            // Import des leads et offres/factures
            $leadsFile = $request->file('leads_file');
            $leadsPath = $leadsFile->getRealPath();
            $leadsData = array_map('str_getcsv', file($leadsPath));
            array_shift($leadsData); // Enlever l'en-tête

            $leadsImported = 0;
            $currentLead = null;
            $currentInvoice = null;
            $currentOffer = null;

            foreach ($leadsData as $index => $row) {
                try {
                    // Trouver le client
                    $client = \App\Models\Client::where('company_name', $row[0])->first();
                    
                    if (!$client) {
                        throw new \Exception("Client non trouvé: " . $row[0]);
                    }

                    // Si c'est un nouveau lead
                    if (!$currentLead || $currentLead->title !== $row[1]) {
                        // Créer le lead
                        $currentLead = \App\Models\Lead::create([
                            'title' => $row[1],
                            'client_id' => $client->id,
                            'status_id' => 1,
                            'user_created_id' => auth()->id(),
                            'user_assigned_id' => auth()->id(),
                            'external_id' => uniqid('LEAD_'),
                            'description' => $faker->sentence(),
                            'deadline' => $faker->dateTimeBetween('now', '+3 months')
                        ]);

                        $leadsImported++;

                        // Créer l'offre ou la facture selon le type
                        if ($row[2] === 'offers') {
                            $currentOffer = \App\Models\Offer::create([
                                'client_id' => $client->id,
                                'external_id' => uniqid('OFFER_'),
                                'status' => 'draft',
                                'source_type' => \App\Models\Lead::class,
                                'source_id' => $currentLead->id
                            ]);
                        } else if ($row[2] === 'invoice') {
                            $currentInvoice = \App\Models\Invoice::create([
                                'client_id' => $client->id,
                                'external_id' => uniqid('INV_'),
                                'status' => 'draft',
                                'source_type' => \App\Models\Lead::class,
                                'source_id' => $currentLead->id
                            ]);
                        }
                    }

                    // Ajouter le produit à l'offre ou à la facture
                    if ($row[2] === 'offers' && $currentOffer) {
                        $currentOffer->invoiceLines()->create([
                            'title' => $row[3],
                            'price' => $row[4] * 100,
                            'quantity' => $row[5],
                            'type' => 'pieces'
                        ]);
                    } else if ($row[2] === 'invoice' && $currentInvoice) {
                        $currentInvoice->invoiceLines()->create([
                            'title' => $row[3],
                            'price' => $row[4] * 100,
                            'quantity' => $row[5],
                            'type' => 'pieces'
                        ]);
                    }

                } catch (\Exception $e) {
                    throw new \Exception("Dans le fichier Leads et Offres/Factures, ligne " . ($index + 2) . ": " . $e->getMessage());
                }
            }

            \DB::commit();
            Session::flash('flash_message', "Importation réussie : {$imported} projets, {$tasksImported} tâches et {$leadsImported} leads importés.");
            
        } catch (\Exception $e) {
            \DB::rollBack();
            Session::flash('flash_message', "Erreur lors de l'importation : " . $e->getMessage());
        }

        return redirect()->back();
    }
}
