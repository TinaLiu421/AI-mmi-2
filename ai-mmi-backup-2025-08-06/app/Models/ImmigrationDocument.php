<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImmigrationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'source_url',
        'title',
        'content',
        'content_clean',
        'section',
        'keywords',
        'word_count',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Search for documents by keywords (full-text search)
     */
    public static function searchByKeywords(string $query, string $country = null, int $limit = 5)
    {
        $builder = self::whereRaw("MATCH(title, content_clean) AGAINST(? IN BOOLEAN MODE)", [$query]);
        
        if ($country) {
            $builder->where('country', $country);
        }
        
        return $builder->limit($limit)->get();
    }

    /**
     * Search by country and section
     */
    public static function searchBySection(string $country, string $section = null, int $limit = 10)
    {
        $builder = self::where('country', $country);
        
        if ($section) {
            $builder->where('section', 'like', "%{$section}%");
        }
        
        return $builder->limit($limit)->get();
    }

    /**
     * Get documents for a specific country
     */
    public static function getByCountry(string $country, int $limit = 10)
    {
        return self::where('country', $country)
            ->limit($limit)
            ->get();
    }

    /**
     * Extract keywords from query (simple implementation)
     */
    public static function extractKeywords(string $query): array
    {
        $words = preg_split('/[\s,\.;!?]+/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out common stopwords
        $stopwords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were'];
        
        return array_filter($words, fn($w) => !in_array($w, $stopwords) && strlen($w) > 2);
    }
}
