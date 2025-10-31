<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentAnalysisService
{
    /**
     * Extract text from uploaded file based on file type
     *
     * @param string $filePath
     * @param string $fileType
     * @return string
     * @throws Exception
     */
    public function extractText(string $filePath, string $fileType): string
    {
        $fileType = strtolower($fileType);

        switch ($fileType) {
            case 'pdf':
                return $this->extractFromPDF($filePath);
            case 'docx':
            case 'doc':
                return $this->extractFromWord($filePath);
            case 'txt':
                return $this->extractFromText($filePath);
            default:
                throw new Exception("Only PDF, Word, and text files are supported. Please upload a PDF document for best results.");
        }
    }

    /**
     * Extract text from PDF file
     *
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    private function extractFromPDF(string $filePath): string
    {
        try {
            // Method 1: Try using smalot/pdfparser PHP library (most reliable for PHP)
            if (class_exists('\Smalot\PdfParser\Parser')) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);
                    $text = $pdf->getText();

                    if ($this->hasContent($text)) {
                        Log::info('PDF extracted via smalot/pdfparser');
                        return trim($text);
                    }
                } catch (Exception $e) {
                    Log::warning('smalot/pdfparser failed, trying alternatives', ['error' => $e->getMessage()]);
                }
            }

            // Method 2: Try using pdftotext command
            $text = $this->tryCommandLineExtraction("pdftotext {input} {output}", $filePath, "pdftotext");
            if ($this->hasContent($text)) {
                return $text;
            }

            // Method 3: Try using pdfbox (Java-based)
            $text = $this->tryCommandLineExtraction("pdfbox ExtractText {input} {output}", $filePath, "pdfbox");
            if ($this->hasContent($text)) {
                return $text;
            }

            // Method 4: Try using mutool (MuPDF)
            $text = $this->tryCommandLineExtraction("mutool draw -t txt {input} > {output}", $filePath, "mutool");
            if ($this->hasContent($text)) {
                return $text;
            }

            // Fallback: use simple PDF parser (works for basic/uncompressed PDFs)
            Log::info('Using fallback PDF parser');
            return $this->simpleExtractFromPDF($filePath);

        } catch (Exception $e) {
            Log::error('PDF extraction failed', ['file' => $filePath, 'error' => $e->getMessage()]);
            throw new Exception($this->getUserFriendlyError($e->getMessage(), 'PDF'));
        }
    }

    /**
     * Simple PDF text extraction (fallback method)
     *
     * @param string $filePath
     * @return string
     */
    private function simpleExtractFromPDF(string $filePath): string
    {
        try {
            $pdfContent = file_get_contents($filePath);
            if (empty($pdfContent)) {
                return '';
            }

            // Try decompressed streams first
            $text = $this->tryDecompressStreams($pdfContent);
            if ($this->hasContent($text)) {
                return trim($text);
            }

            // Try BT...ET text blocks
            $text = $this->extractTextFromBTET($pdfContent);
            if ($this->hasContent($text)) {
                return trim($text);
            }

            // Try all text strings
            $text = $this->extractAllTextStrings($pdfContent);
            if ($this->hasContent($text)) {
                return trim($text);
            }

            // Last resort: extract readable ASCII
            $text = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $pdfContent);
            $text = preg_replace('/\s+/', ' ', $text);

            return trim($text);

        } catch (Exception $e) {
            Log::warning('Simple PDF extraction failed', ['file' => $filePath, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Extract text from BT...ET blocks (text objects)
     *
     * @param string $pdfContent
     * @return string
     */
    private function extractTextFromBTET(string $pdfContent): string
    {
        $text = '';

        if (!preg_match_all('/BT\s+(.*?)\s+ET/s', $pdfContent, $matches)) {
            return '';
        }

        foreach ($matches[1] as $block) {
            // Tj operator (single string)
            if (preg_match_all('/\((.*?)\)\s*Tj/', $block, $textMatches)) {
                foreach ($textMatches[1] as $extractedText) {
                    $decoded = $this->decodePDFString($extractedText);
                    if ($this->hasContent($decoded)) {
                        $text .= $decoded . ' ';
                    }
                }
            }

            // TJ operator (array of strings)
            if (preg_match_all('/\[(.*?)\]\s*TJ/', $block, $tjMatches)) {
                foreach ($tjMatches[1] as $tjBlock) {
                    if (preg_match_all('/\(([^)]*)\)/', $tjBlock, $tjTextMatches)) {
                        foreach ($tjTextMatches[1] as $extractedText) {
                            $decoded = $this->decodePDFString($extractedText);
                            if ($this->hasContent($decoded)) {
                                $text .= $decoded . ' ';
                            }
                        }
                    }
                }
            }

            // ' operator (show text with positioning)
            if (preg_match_all('/\((.*?)\)\s*\'/', $block, $singleQuoteMatches)) {
                foreach ($singleQuoteMatches[1] as $extractedText) {
                    $decoded = $this->decodePDFString($extractedText);
                    if ($this->hasContent($decoded)) {
                        $text .= $decoded . ' ';
                    }
                }
            }

            // " operator (set spacing and show text)
            if (preg_match_all('/\((.*?)\)\s*"/', $block, $doubleQuoteMatches)) {
                foreach ($doubleQuoteMatches[1] as $extractedText) {
                    $decoded = $this->decodePDFString($extractedText);
                    if ($this->hasContent($decoded)) {
                        $text .= $decoded . ' ';
                    }
                }
            }
        }

        return trim($text);
    }

    /**
     * Extract all text strings from entire PDF
     *
     * @param string $pdfContent
     * @return string
     */
    private function extractAllTextStrings(string $pdfContent): string
    {
        $text = '';

        // Extract all parenthesized strings in PDF
        if (preg_match_all('/\(([^()]+)\)/', $pdfContent, $matches)) {
            foreach ($matches[1] as $extractedText) {
                $decoded = $this->decodePDFString($extractedText);
                // Only keep if it looks like real text (contains alphanumeric)
                if (preg_match('/[a-zA-Z0-9]/', $decoded)) {
                    $text .= $decoded . ' ';
                }
            }
        }

        return trim($text);
    }

    /**
     * Try to decompress and extract from PDF streams
     *
     * @param string $pdfContent
     * @return string
     */
    private function tryDecompressStreams(string $pdfContent): string
    {
        $text = '';
        try {
            if (preg_match_all('/stream\s+(.*?)\s+endstream/s', $pdfContent, $streamMatches)) {
                foreach ($streamMatches[1] as $stream) {
                    if (!empty($stream) && ord($stream[0]) === 0x78) {
                        $decompressed = @gzuncompress($stream);
                        if ($decompressed !== false && !empty($decompressed)) {
                            if (preg_match_all('/\((.*?)\)\s*[Tj]/', $decompressed, $matches)) {
                                foreach ($matches[1] as $extractedText) {
                                    $text .= $this->decodePDFString($extractedText) . ' ';
                                }
                            }
                            if (!$this->hasContent($text)) {
                                $readable = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $decompressed);
                                $readable = preg_replace('/\s+/', ' ', $readable);
                                if ($this->hasContent($readable)) {
                                    $text .= $readable . ' ';
                                }
                            }
                        }
                    }
                }
            }
            return trim($text);
        } catch (Exception $e) {
            Log::debug('Stream decompression attempt failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Decode PDF string format (escape sequences, octal, hex)
     *
     * @param string $str
     * @return string
     */
    private function decodePDFString(string $str): string
    {
        $str = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $str);
        $str = str_replace('\\\\', '\\', $str);

        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function($matches) {
            return chr(octdec($matches[1]));
        }, $str);

        $str = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/i', function($matches) {
            return chr(hexdec($matches[1]));
        }, $str);

        $str = preg_replace('/\\\\[^nrt]/', '', $str);
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        $str = preg_replace('/\s+/', ' ', trim($str));

        return $str;
    }

    /**
     * Extract text from Word document (.docx)
     *
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    private function extractFromWord(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();

            if ($zip->open($filePath) !== true) {
                throw new Exception('Failed to open DOCX file');
            }

            $xmlString = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xmlString === false) {
                throw new Exception('Failed to read document.xml from DOCX');
            }

            $xml = new \DOMDocument();
            $xml->loadXML($xmlString);

            $xpath = new \DOMXPath($xml);
            $textNodes = $xpath->query('//w:t');

            $text = '';
            foreach ($textNodes as $node) {
                $text .= $node->nodeValue . ' ';
            }

            return trim($text);

        } catch (Exception $e) {
            Log::error('Word document extraction failed', ['file' => $filePath, 'error' => $e->getMessage()]);
            throw new Exception($this->getUserFriendlyError($e->getMessage(), 'Word'));
        }
    }

    /**
     * Extract text from image using OCR (requires Tesseract)
     *
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    private function extractFromImage(string $filePath): string
    {
        try {
            $output = shell_exec("which tesseract 2>/dev/null");

            if (!$output) {
                Log::warning('Tesseract not installed, returning filename as text');
                return basename($filePath);
            }

            $tempOutput = tempnam(sys_get_temp_dir(), 'ocr_extract_');
            $command = escapeshellcmd("tesseract {$filePath} {$tempOutput} -l eng");

            shell_exec($command . " 2>&1");

            if (file_exists($tempOutput . '.txt')) {
                $text = file_get_contents($tempOutput . '.txt');
                unlink($tempOutput . '.txt');
                return trim($text);
            }

            Log::warning('Tesseract OCR produced no output', ['file' => $filePath]);
            return '';

        } catch (Exception $e) {
            Log::error('Image OCR extraction failed', ['file' => $filePath, 'error' => $e->getMessage()]);
            throw new Exception($this->getUserFriendlyError($e->getMessage(), 'image'));
        }
    }

    /**
     * Extract text from plain text file
     *
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    private function extractFromText(string $filePath): string
    {
        try {
            $text = file_get_contents($filePath);

            if ($text === false) {
                throw new Exception('Failed to read text file');
            }

            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8,GBK,GB2312,CP936,ISO-8859-1');
            }

            return trim($text);

        } catch (Exception $e) {
            Log::error('Text file extraction failed', ['file' => $filePath, 'error' => $e->getMessage()]);
            throw new Exception($this->getUserFriendlyError($e->getMessage(), 'text'));
        }
    }

    /**
     * Analyze extracted text using Gemini API
     *
     * @param string $text
     * @param string $analysisType
     * @return array
     * @throws Exception
     */
    public function analyzeText(string $text, string $analysisType = 'comprehensive'): array
    {
        try {
            $maxLength = 50000;
            if (strlen($text) > $maxLength) {
                $text = substr($text, 0, $maxLength) . '...';
            }

            $prompt = $this->buildAnalysisPrompt($text, $analysisType);
            $analysisResult = $this->callGeminiAPI($prompt);

            return [
                'type' => $analysisType,
                'result' => $analysisResult,
                'text_length' => strlen($text),
                'analysis_timestamp' => now()->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::error('Text analysis failed', ['error' => $e->getMessage()]);
            throw new Exception('Unable to analyze this document. Please try again or upload a different file.');
        }
    }

    /**
     * Build analysis prompt based on type
     *
     * @param string $text
     * @param string $analysisType
     * @return string
     */
    private function buildAnalysisPrompt(string $text, string $analysisType): string
    {
        $basePrompt = "You are an immigration and study abroad advisor. Analyze this document:\n\n{$text}\n\n";

        switch ($analysisType) {
            case 'extraction':
                return $basePrompt . "Extract and return ONLY a JSON object with these fields:\n"
                    . "{\n"
                    . '  "names": ["list of person names found"],\n'
                    . '  "dates": ["list of dates found"],\n'
                    . '  "emails": ["list of email addresses"],\n'
                    . '  "phone_numbers": ["list of phone numbers"],\n'
                    . '  "amounts": ["list of monetary amounts"],\n'
                    . '  "organizations": ["list of organization/company names"],\n'
                    . '  "addresses": ["list of addresses"],\n'
                    . '  "key_terms": ["list of important keywords"]\n'
                    . "}\n"
                    . "Return ONLY valid JSON, no additional text.";

            case 'summary':
                return $basePrompt . "Provide:\n"
                    . "1. A brief 2-3 sentence summary\n"
                    . "2. Main topics covered (bullet points)\n"
                    . "3. Key findings or conclusions\n"
                    . "4. Document type classification (e.g., student visa, passport, degree certificate, IELTS result, letter of offer)";

            case 'comprehensive':
            default:
                return $basePrompt . "You are a friendly immigration advisor reviewing this document.\n\n"
                    . "RESPOND CONVERSATIONALLY:\n"
                    . "- Acknowledge what you see in the document naturally\n"
                    . "- Keep your response SHORT and simple (2-3 sentences max)\n"
                    . "- NO bullet points, NO bold text, NO special formatting\n"
                    . "- Ask ONE simple follow-up question if needed\n"
                    . "- Sound like a real person having a conversation\n\n"
                    . "Example responses:\n"
                    . "If academic record: 'That's a great GPA! I see you studied at [university]. What's your current visa situation?'\n"
                    . "If passport: 'Got your passport info. When does it expire?'\n"
                    . "If visa document: 'Your visa looks valid until [date]. Are you planning to stay in Australia after that?'\n"
                    . "If test scores: 'Nice IELTS score of [X]. That works well for most programs.'\n\n"
                    . "KEEP IT SHORT AND NATURAL - like texting a friend, not a formal document.";
        }
    }

    /**
     * Check if text has meaningful content
     *
     * @param string $text
     * @return bool
     */
    private function hasContent(string $text): bool
    {
        return !empty(trim($text));
    }

    /**
     * Try extracting text using a command-line tool
     *
     * @param string $command Command to execute (use {input} and {output} placeholders)
     * @param string $filePath File to extract from
     * @param string $methodName Name of method for logging
     * @param string $outputExtension Optional file extension for output
     * @return string Extracted text or empty string
     */
    private function tryCommandLineExtraction(string $command, string $filePath, string $methodName, string $outputExtension = ''): string
    {
        try {
            $toolName = strtok($command, ' ');
            if (!shell_exec("which {$toolName} 2>/dev/null")) {
                return '';
            }

            $tempOutput = tempnam(sys_get_temp_dir(), 'extract_');
            $escapedPath = escapeshellarg($filePath);
            $escapedOutput = escapeshellarg($tempOutput);

            // Build the full command
            $fullCommand = str_replace(
                ['{input}', '{output}'],
                [$escapedPath, $escapedOutput],
                $command
            );

            shell_exec($fullCommand . " 2>/dev/null");

            $outputFile = $outputExtension ? ($tempOutput . $outputExtension) : $tempOutput;

            if (!file_exists($outputFile) || filesize($outputFile) === 0) {
                @unlink($tempOutput);
                if (file_exists($outputFile)) {
                    @unlink($outputFile);
                }
                return '';
            }

            $text = file_get_contents($outputFile);
            @unlink($tempOutput);
            @unlink($outputFile);

            if ($this->hasContent($text)) {
                Log::info("Text extracted via {$methodName}");
                return trim($text);
            }

            return '';
        } catch (Exception $e) {
            Log::warning("Command extraction failed for {$methodName}", ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Call Gemini API for text analysis
     *
     * @param string $prompt
     * @return string
     * @throws Exception
     */
    private function callGeminiAPI(string $prompt): string
    {
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://generativelanguage.googleapis.com',
                'timeout' => 60,
            ]);

            $model = env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash');
            $apiKey = env('GEMINI_API_KEY');

            if (!$apiKey) {
                throw new Exception('GEMINI_API_KEY is not configured');
            }

            $payload = [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 1,
                    'topP' => 1.0,
                    'maxOutputTokens' => (int) env('GEN_MAX_TOKENS', 2048),
                ],
            ];

            $response = $client->post("/v1beta/models/{$model}:generateContent", [
                'headers' => [
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                ),
            ]);

            $data = json_decode((string) $response->getBody(), true) ?? [];

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        } catch (Exception $e) {
            Log::error('Gemini API call failed', ['error' => $e->getMessage()]);
            throw new Exception('Gemini API error: ' . $e->getMessage());
        }
    }

    /**
     * Convert technical error messages to user-friendly messages
     *
     * @param string $errorMessage
     * @param string $fileType
     * @return string
     */
    private function getUserFriendlyError(string $errorMessage, string $fileType = 'document'): string
    {
        // Check for shell_exec disabled error
        if (stripos($errorMessage, 'shell_exec') !== false && stripos($errorMessage, 'disabled') !== false) {
            return 'This format is not supported yet. Please upload the original PDF file.';
        }

        // Check for image/OCR related errors
        if (stripos($errorMessage, 'tesseract') !== false || stripos($errorMessage, 'image') !== false) {
            return 'This image format is not supported yet. Please upload a PDF or Word document instead.';
        }

        // Check for unsupported file type
        if (stripos($errorMessage, 'unsupported') !== false || stripos($errorMessage, 'not supported') !== false) {
            return 'This file format is not supported. Please upload a PDF, Word document, or text file.';
        }

        // Check for extraction failures
        if (stripos($errorMessage, 'failed to extract') !== false || stripos($errorMessage, 'extraction') !== false) {
            return 'Unable to read this file. Please make sure it is a valid ' . strtoupper($fileType) . ' file and try again.';
        }

        // Generic fallback
        return 'Unable to process this file. Please try uploading a different file format (PDF recommended).';
    }
}
