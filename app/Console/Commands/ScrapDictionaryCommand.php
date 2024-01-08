<?php

namespace App\Console\Commands;

use App\Models\Word;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
class ScrapDictionaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:dictionary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scraps dictionary explanations from a website';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting dictionary scraping...');

        $files = [
            'txts/c1.txt',
            'txts/c2.txt',
            'txts/c3.txt',
            'txts/c4.txt',
            'txts/c5.txt',
            'txts/c6.txt',
            'txts/v1.txt',
            'txts/v2.txt',
        ];

        $allContentsArray = [];

        foreach ($files as $file) {
            $contentsArray = explode("\n", file_get_contents(database_path($file)));
            $allContentsArray = array_merge($allContentsArray, $contentsArray);
        }

        $collection = collect($allContentsArray);

        $parameters = $collection->reject(function ($value) {
            return is_null($value) || $value === "";
        })->unique();

        foreach ($parameters as $key => $parameter) {
            // Construct the URL for each word
            $url = 'https://www.kamus.yek.gov.tr/Home/Getdata?id=' . rawurlencode($parameter);

            // Scrape the dictionary for the current word
            $this->info("Crawling word {$key}: " . $parameter);

            $this->crawlWords($url,$parameter);
        }

        $this->info('Scraping completed.');
    }

    protected function crawlWords($url,$parameter)
    {
        $response = Http::withoutVerifying()->get($url);
        $body = $response->body();
        $body = str_replace(' padding-left :0px','main',$body);
        $body = str_replace('text-align :center;font-size :18px;margin:0 auto ;display :block ;text-align :center; color :#F1C40F;margin-top:0px;margin-bottom:0px;background-color :#333 ;padding-top :10px; padding-bottom:15px','dictionary',$body);
        $body = str_replace(' padding :15px','second',$body);
        $body = str_replace(' margin-top :20px; font-family: TimesEfras; font-size :18px; line-height :24px;color :black;text-align:justify','content',$body);
        $body = str_replace(' ;background-color:white','',$body);
        $body = str_replace(' margin-top :20px; font-family: TimesEfras; font-size :18px; line-height :24px;color :black;text-align:justify','content',$body);

        $crawler = new Crawler($body);
        $mainDivs = $crawler->filter('div[style="main"]');
        $muhitDiv = $mainDivs->first();
        $vankuluDiv = $mainDivs->last();

        $muhitContents = collect($muhitDiv->filter('div[style="content"]')->each(function (Crawler $div) {
            return $div->text();
        }))->map(function ($text) {
            $extracted = self::extract($text);
            $extracted["dictionary"] =  "Muhit";
            return $extracted;
        });

        $vankuluContents = collect($vankuluDiv->filter('div[style="content"]')->each(function (Crawler $div) {
            return $div->text();
        }))->map(function ($text) {
            $extracted = self::extract($text);
            $extracted["dictionary"] =  "Vankulu";
            return $extracted;
        });

        $contents = $muhitContents->merge($vankuluContents);

        if ($contents->count() > 0)
        {
            foreach ($contents as $content)
            {
                $arabicText = self::cleanArabicText($content["arabic"]);
                $explanation = self::cleanArabicText($content["explanation"]);

                $text = $arabicText;
                $dictionaryName = $content["dictionary"];
                $pronunciation = $content["pronunciation"];

                $this->createWordEntry($dictionaryName, $text, $pronunciation, $explanation, $parameter, $url);
            }
        }else{
            $this->createWordEntry('None', 'None', 'None', 'None', $parameter, $url);
        }

    }

    protected function createWordEntry($dictionary, $text, $pronunciation, $explanation, $parameter, $url)
    {
        // Create a new Word entry
        return Word::firstOrCreate([
            'dictionary' => $dictionary,
            'text' => $text,
            'pronunciation' => $pronunciation,
            'explanation' => $explanation,
            'parameter' => $parameter,
            'url' => $url,
        ]);
    }

    protected static function cleanArabicText($text) {
        // Remove diacritics
        $text = preg_replace('/[ًٌٍَُِّْ]/u', '', $text);

        // Replace Alef
        $text = str_replace(['أ', 'آ', 'إ'], 'ا', $text);

        // Replace Ya
        $text = str_replace(['ى'], 'ي', $text);

        return $text;
    }

    protected function extract($text)
    {
        // Extract pronunciation and explanation from the text
        // Adjust the logic based on the actual structure of the text
        $matches = [];
        preg_match('/(.*?)\[(.*?)\]/', $text, $matches);

        if (count($matches) >= 3) {
            $arabicText = trim($matches[1]);
            $arabicText = (strlen($arabicText) > 30) ? 'None' : $arabicText;

            $pronunciation = trim($matches[2]);
            $pronunciation = (strlen($pronunciation) > 30) ? 'None' : $pronunciation;

            $explanation = trim(str_replace("$arabicText", '', $text)); // Use $explanation instead of $text
            $explanation = trim(str_replace("[$pronunciation]", '', $explanation)); // Use $explanation instead of $text

            return [
                'arabic' => $arabicText,
                'pronunciation' => $pronunciation,
                'explanation' => $explanation,
            ];
        }

        return [null, $text];
    }
}
