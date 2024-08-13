<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class initial_Brainframe_seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folderPath = storage_path('brainframe_data_seed');
        $files = [
            'bf_roles.csv',
            'bf_methods.csv',
            'bf_methods_roles.csv',
            'migrations.csv'
        ];

        foreach ($files as $file) {
            $tableName = pathinfo($file, PATHINFO_FILENAME);

            $filePath = $folderPath . '/' . $file;
            if (!File::exists($filePath)) {
                $this->command->error("File $file does not exist in $folderPath.");
                continue;
            }

            $data = $this->csvToArray($filePath);

            if (!empty($data)) {
                DB::table($tableName)->insert($data);
                $this->command->info("Inserted data from $file into $tableName.");
            } else {
                $this->command->warn("No data to insert for $file.");
            }
        }
    }

    /**
     * Convert CSV file to an array.
     */
    private function csvToArray(string $filename): array
    {
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            $header = null;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $rowData = array_combine($header, $row);

                    // Überprüfen und Setzen von gültigen Zeitstempeln für created_at und updated_at
                    if (isset($rowData['created_at']) && empty($rowData['created_at'])) {
                        $rowData['created_at'] = Carbon::now()->toDateTimeString();
                    }

                    if (isset($rowData['updated_at']) && empty($rowData['updated_at'])) {
                        $rowData['updated_at'] = Carbon::now()->toDateTimeString();
                    }

                    $data[] = $rowData;
                }
            }
            fclose($handle);
        }
        return $data;
    }
}
