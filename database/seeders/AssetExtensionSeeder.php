<?php
namespace Database\Seeders;

use App\Models\AssetExtension;
use Illuminate\Database\Seeder;

class AssetExtensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $extensions = [
            // Text Files
            'txt', 'doc', 'docx', 'rtf', 'pdf', 'odt',

            // Image Files
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif', 'svg',

            // Audio Files
            'mp3', 'wav', 'aac', 'wma', 'mid', 'midi',

            // Video Files
            'mp4', 'avi', 'mov', 'wmv', 'flv',

            // Spreadsheet Files
            'xls', 'xlsx', 'csv', 'ods',

            // Archive Files
            // 'zip', 'rar', '7z', 'tar', 'gz',

            // Other Files
            // 'exe', 'html', 'htm', 'css', 'js', 'py', 'sql', 'xml', 'bak', 'tmp',
        ];

        foreach ($extensions as $ext) {
            AssetExtension::firstOrCreate(['extension' => $ext]);
        }
    }
}
