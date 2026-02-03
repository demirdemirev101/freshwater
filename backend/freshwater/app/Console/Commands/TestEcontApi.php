<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestEcontApi extends Command
{
    protected $signature = 'test:econt-api';
    protected $description = 'Test Econt Demo API';

   public function handle()
{
    $this->info('Testing Econt Demo API...');
    $this->info('URL: https://demo.econt.com/ee/services');
    $this->info('Username: iasp-dev');
    $this->info('Password: 1Asp-dev');
    $this->newLine();
    
    // Test 1: Nomenclatures
    $this->info('Test 1: getCities...');
    $response = Http::withOptions([
            'verify' => false,
            'debug' => false,
        ])
        ->timeout(30)
        ->withBasicAuth('iasp-dev', '1Asp-dev')
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->post('https://demo.econt.com/ee/services/Nomenclatures/NomenclaturesService.getCities.json', [
            'countryCode' => 'BGR',
            'name' => 'София',
        ]);

    $this->info("Status Code: " . $response->status());
    
    if ($response->successful()) {
        $data = $response->json();
        $this->info("✅ SUCCESS!");
        $this->info(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } else {
        $this->error("❌ FAILED");
        $this->error("Response: " . $response->body());
        
        // Try production URL
        $this->newLine();
        $this->warn('Trying production URL...');
        
        $response2 = Http::withOptions(['verify' => false])
            ->timeout(30)
            ->withBasicAuth('iasp-dev', 'iasp-dev')
            ->post('https://ee.econt.com/services/Nomenclatures/NomenclaturesService.getCities.json', [
                'countryCode' => 'BGR',
                'name' => 'София',
            ]);
            
        $this->info("Production Status: " . $response2->status());
        $this->info("Production Response: " . $response2->body());
    }

    $this->newLine();
    $this->info('Test 2: getOffices for Sofia...');
    
    $response2 = Http::withOptions(['verify' => false])
        ->withBasicAuth('iasp-dev', '1Asp-dev')
        ->post('https://demo.econt.com/ee/services/Nomenclatures/NomenclaturesService.getOffices.json', [
            'countryCode' => 'BGR',
            'cityId' => 68134, // ID на София
        ]);
    
    $this->info("Status: " . $response2->status());
    
    if ($response2->successful()) {
        $offices = $response2->json('offices', []);
        $this->info("Found " . count($offices) . " offices");
        
        foreach (array_slice($offices, 0, 3) as $office) {
            $this->line("  • " . ($office['name'] ?? 'N/A') . 
                       " (" . ($office['code'] ?? 'N/A') . ")");
        }
    }
}
}