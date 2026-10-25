<?php

namespace Modules\Sviat\Ringostat;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Плеєр запису підключається прямим тегом, повз бандл, а nginx віддає дерево
 * модулів із `max-age` на десять років. Без кеш-мітки правка цього файлу не
 * доїжджає в браузер, який уже його завантажив: розмітка нова, скрипт старий.
 *
 * Той самий запобіжник, що й у ядра для вибирача файлів
 * (tests/Admin/Controllers/FilePickerAdminScriptVersionTest).
 */
class RecordPlayerScriptVersionTest extends TestCase
{
    private const SCRIPT = 'ringostat_record_player.js';

    /** @dataProvider templateProvider */
    #[DataProvider('templateProvider')]
    public function testScriptIsVersionedByItsOwnModificationTime(string $template, string $variable): void
    {
        $source = $this->read('Okay/Modules/Sviat/Ringostat/Backend/design/html/' . $template);

        $this->assertStringContainsString(
            self::SCRIPT . '?v={' . $variable,
            $source,
            $template . ': скрипт плеєра підключено без кеш-мітки'
        );
    }

    public static function templateProvider(): array
    {
        return [
            'журнал дзвінків'   => ['ringostat_calls.tpl', '$ringostat_player_version'],
            'картка замовлення' => ['order_ringostat_calls.tpl', '$order->sviat_ringostat_player_version'],
        ];
    }

    /** Мітка мусить бути часом самого файлу, а не версією CMS. */
    public function testVersionComesFromTheScriptModificationTime(): void
    {
        $helper = $this->read('Okay/Modules/Sviat/Ringostat/Backend/Helpers/RingostatBackendHelper.php');

        $this->assertStringContainsString('function recordPlayerVersion', $helper);
        $this->assertStringContainsString('filemtime(', $helper);
        $this->assertStringContainsString(self::SCRIPT, $helper);
    }

    /** Обидва шляхи беруть мітку з одного джерела, інакше вона розійдеться. */
    /** @dataProvider versionSourceProvider */
    #[DataProvider('versionSourceProvider')]
    public function testBothRenderPathsUseTheSharedHelper(string $file, string $assignment): void
    {
        $source = $this->read($file);

        $this->assertStringContainsString($assignment, $source);
        $this->assertStringContainsString('RingostatBackendHelper::recordPlayerVersion()', $source);
    }

    public static function versionSourceProvider(): array
    {
        return [
            'контролер журналу' => [
                'Okay/Modules/Sviat/Ringostat/Backend/Controllers/RingostatCallsAdmin.php',
                "assign('ringostat_player_version', ",
            ],
            'екстендер замовлення' => [
                'Okay/Modules/Sviat/Ringostat/Extenders/BackendExtender.php',
                'sviat_ringostat_player_version = ',
            ],
        ];
    }

    /** Обидва шляхи мусять указувати на той самий файл, інакше мітки розійдуться. */
    public function testBothPathsPointAtTheSameScript(): void
    {
        $this->assertFileExists(
            $this->root() . '/Okay/Modules/Sviat/Ringostat/Backend/design/js/' . self::SCRIPT
        );
    }

    private function read(string $relativePath): string
    {
        return file_get_contents($this->root() . '/' . $relativePath);
    }

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }
}
