<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImmigrationRagService;

class IngestImmigrationDocuments extends Command
{
    protected $signature = 'immigration:ingest {--force : Force re-ingestion of all documents}';
    protected $description = 'Ingest immigration documents from Australian and NZ government websites';

    public function handle(ImmigrationRagService $service)
    {
        $this->info('🌐 Starting immigration document ingestion...');
        $this->newLine();
        
        $results = $service->ingestAllSources();
        
        // Display results
        $totalWords = 0;
        $totalDocs = 0;
        
        foreach ($results as $key => $result) {
            if (isset($result['error'])) {
                $this->error("❌ {$key}: {$result['error']}");
            } elseif ($result['status'] === 'created') {
                $this->info("✅ Created: {$key} ({$result['words']} words)");
                $totalWords += $result['words'];
                $totalDocs += 1;
            } elseif ($result['status'] === 'updated') {
                $this->line("♻️  Updated: {$key} ({$result['words']} words)");
                $totalWords += $result['words'];
                $totalDocs += 1;
            } else {
                $this->warn("⚠️  {$key}: " . json_encode($result));
            }
        }
        
        $this->newLine();
        $this->info("📊 Summary: {$totalDocs} documents, {$totalWords} total words ingested");
        $this->info('✨ Immigration RAG setup complete!');
        
        return self::SUCCESS;
    }
}
