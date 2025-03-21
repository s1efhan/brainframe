<?php
namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class InitialSetup extends Seeder
{
    /**
    * Run the database seeds.
    *
    * @return void
    */
    public function run()
    {
        // Zuerst die Basis-Tabellen befüllen
        $this->seedFromCsv('bf_methods', '/resources/brainframe/seed/bf_methods.csv');
        $this->seedFromCsv('bf_roles', '/resources/brainframe/seed/bf_roles.csv');
        
        // Dann die Beziehungstabelle
        $this->seedFromCsv('bf_methods_roles', '/resources/brainframe/seed/bf_methods_roles.csv');
        
        $this->command->info('Initial setup completed successfully!');
    }
    
    /**
    * Seed a table from a CSV file
    *
    * @param string $table The table name
    * @param string $csvPath The path to the CSV file
    * @return void
    */
    private function seedFromCsv($table, $csvPath)
    {
        $this->command->info("Seeding table: {$table}");
        
        // Get the full path to the CSV file
        $fullPath = base_path() . $csvPath;
        
        if (!file_exists($fullPath)) {
            $this->command->error("CSV file not found: {$fullPath}");
            return;
        }
        
        // Truncate the table to remove existing records
        Schema::disableForeignKeyConstraints();
        DB::table($table)->truncate();
        Schema::enableForeignKeyConstraints();
        
        // Open the CSV file
        $file = fopen($fullPath, 'r');
        
        // Get the header row
        $headers = fgetcsv($file);
        
        // Get current timestamp for created_at and updated_at
        $now = Carbon::now()->toDateTimeString();
        
        // Read the data rows and insert them into the table
        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);
            
            // Handle timestamp fields
            if (isset($data['created_at']) && empty($data['created_at'])) {
                $data['created_at'] = $now;
            }
            
            if (isset($data['updated_at']) && empty($data['updated_at'])) {
                $data['updated_at'] = $now;
            }
            
            DB::table($table)->insert($data);
            $count++;
        }
        
        fclose($file);
        
        $this->command->info("Imported {$count} records into {$table}");
    }
}