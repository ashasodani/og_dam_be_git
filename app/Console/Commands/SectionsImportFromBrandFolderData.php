<?php
namespace App\Console\Commands;

use App\Models\AssetExtension;
use App\Models\Assets;
use App\Models\Sections;
use App\Models\Tags;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Jobs\UploadThumbToS3;
use App\Jobs\UploadAssetToS3;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;

class SectionsImportFromBrandFolderData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brandfolder:import-sections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Sections and related Assets from Brandfolder into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching Brandfolder section data...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');
        $baseUrl = "https://brandfolder.com/api/v4/brandfolders/{$slug}/sections";

        $workspaceId = Workspaces::where('slug', 'degeest')->value('id');
        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }

        $typeMap = [
            'GenericFile' => 'files',
            'Color'       => 'colors',
            'Press'       => 'press/links',
        ];
        
        $page    = 1;
        $perPage = 25;

        do {
            $response = Http::withToken($apiToken)->get($baseUrl, [
                'page'     => $page,
                'per_page' => $perPage,
            ]);

            if (! $response->successful()) {
                $this->error('Failed to fetch page ' . $page . ': ' . $response->body());
                break;
            }

            $data     = $response->json('data') ?? [];
            $meta     = $response->json('meta') ?? [];
            $lastPage = $meta['last_page'] ?? $page;
           // dd($data);
            foreach ($data as $sectionItem) {
                $attributes  = $sectionItem['attributes'] ?? [];
                $sectionName = $attributes['name'] ?? null;
                $sectionType = $attributes['default_asset_type'] ?? null;

                if (! $sectionName || ! isset($typeMap[$sectionType])) {
                    $this->warn("Skipped invalid or unsupported section: {$sectionName}");
                    continue;
                }

                $mappedType = $typeMap[$sectionType];
                if(! $mappedType){
                    
                }
               // if($sectionItem['id']=="4t8wp5ccb9h8kwh4gs34rwx4"){
                    $section = Sections::firstOrCreate(
                        ['name' => $sectionName],
                        [
                            'asset_type' => $mappedType,
                            'position'   => $attributes['position'] ?? null,
                        ]
                    );
              

                    // Attach to workspace
                    $section->workspaces()->syncWithoutDetaching([$workspaceId]);

                    /* asset import start */
                    $sectionId = $sectionItem['id'];
                    //$sectionUrl = "https://brandfolder.com/api/v4/sections/jkw5h9m8nr884z3qhshp7p8/assets?include=attachments";
                    $sectionUrl = "https://brandfolder.com/api/v4/sections/{$sectionId}/assets?include=attachments";
                
                    $sectionResponse = Http::withToken($apiToken)->get($sectionUrl, [
                        'fast_jsonapi'   => true,
                        'per'            => 3000,
                        'queue_priority' => 'high',
                        'include'=>'attachments',
                    ]);
               
                    if (! $sectionResponse->successful()) {
                        $this->error("Failed to fetch assets for section ID: $sectionId");
                        continue;
                    }

                    $sectiondata = $sectionResponse->json('data') ?? [];
                    //$sectiondata = $sectionResponse->json('data') ?? [];
                   // dd($sectiondata);
                    foreach ($sectiondata as $assetItem) {
                        $attr = $assetItem['attributes'] ?? [];
                        $extension = $attr['extension'] ?? 'png';
                        $filename = Str::slug($attr['name'] ?? 'asset') . '.' . $extension;
                        $cmykData = [
                            "c" => $assetItem['attributes']['asset_data']['c']??null,
                            "m" => $assetItem['attributes']['asset_data']['m']??null,
                            "y" => $assetItem['attributes']['asset_data']['y']??null,
                            "k" => $assetItem['attributes']['asset_data']['k']??null,
                        ];
                        $values = array_map(fn($v) => $v ?? '', $cmykData);

                        // join with commas
                        $cmyk = implode(',', $values);

                        $rgbData = [
                            "r" => $assetItem['attributes']['asset_data']['r']??null,
                            "g" => $assetItem['attributes']['asset_data']['g']??null,
                            "b" => $assetItem['attributes']['asset_data']['b']??null,
                        ];
                        $values = array_map(fn($v) => $v ?? '', $rgbData);
                        // join with commas
                        $rgb = implode(',', $values);
                        $hexa = $attr['asset_data']['hex'] ?? null;
                        if($hexa){
                            $hexa = "#".$hexa;
                        }
                        if (!empty($attr['asset_data']['published_date'])) {
                            $date = \DateTime::createFromFormat('d/m/Y', $attr['asset_data']['published_date']);
                            if ($date) {
                                $attr['asset_data']['published_date'] = $date->format('Y-m-d');
                            } else {
                                // fallback if date is invalid
                                $attr['asset_data']['published_date'] = null;
                            }
                        }
                       // dd($attr);
                        $asset = Assets::firstOrCreate(
                            ['asset_key' => $assetItem['id']],
                            [
                                'name'            => $attr['name'] ?? null,
                                //'type'            => $attr['type'] ?? null,
                                'description'     => $attr['description'] ?? null,
                                'asset_key'       => $assetItem['id'],
                                'filename'        => Str::slug($attr['name'] ?? 'asset') . '.png',
                                'extension'       => $attr['extension'] ?? 'png',
                                'publish_date'    => $attr['asset_data']['published_date'] ?? null,
                                'links'    => $attr['asset_data']['url'] ?? null,
                                'hex'    => $hexa??null,
                                'pantagone_coated'    => $attr['asset_data']['pantone_u'] ?? null,
                                'cmyk'    => $cmyk ?? null,
                                'rgb'    => $rgb ?? null,
                                'is_completed'    => true,
                                'created_by'            => 4,
                            ]
                        );

                        // Attach asset to section via pivot table
                        $section->assets()->syncWithoutDetaching([$asset->id]);
                        $asset->workspaces()->attach([$workspaceId]);
                        // Save extension in asset_extensions table
                        AssetExtension::firstOrCreate(['extension' => $extension]);

                            // tags in a assets
                        $tagNames = $attr['tag_names'] ?? [];
                        foreach ($tagNames as $tagName) {
                            $tag = Tags::firstOrCreate(
                                ['name' => $tagName],
                            );

                            // Attach to workspace
                            $tag->workspaces()->syncWithoutDetaching([$workspaceId]);

                            // Attach asset to tag
                            $tag->assets()->syncWithoutDetaching([$asset->id]);
                        }
                    
                        $path = config('deegest.asset_document.asset_file_path');
                        $attr['thumbnail_url'] = $attr['thumbnail_url'] ?? null;
                        $height = 268;$width = 422;
                        //UploadThumbToS3::dispatch($attr, $asset->id,$path,$table = 'assets',$height,$width);
                            // dd($attr);
                                //if(isset($attr['asset_data']['url'])){
                            //  $assetPath = config('deegest.asset_document.asset_file_path').$asset->id . '/' . $filename;
                                // UploadAssetToS3::dispatch($attr, $asset->id,$assetPath,$assetItem['id'])->onQueue('assets');;
                                //}
                            //         //dd("op");
                    }
                
                    $this->info("Imported section and assets: " . $section->name);
               // }
            }
            $page++;
        } while ($page <= $lastPage);

        $this->info('All sections and their assets have been imported successfully.');
    }
}
