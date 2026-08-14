<?php

namespace Modules\Sviat\Ringostat;

use Okay\Modules\Sviat\Ringostat\Backend\Helpers\RingostatBackendHelper as Helper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * У черзі передзвону рядок ідентифікується телефоном, а не id: список похідний
 * від журналу дзвінків. Отже, телефон приїжджає з форми як ключ масиву й іде
 * далі в запит — усе, що не схоже на номер, має відсіятись тут.
 */
class RingostatBackendHelperTest extends TestCase
{
    /** @dataProvider validPhoneProvider */
    #[DataProvider('validPhoneProvider')]
    public function testPhoneFromFormIsAccepted(string $input, string $expected): void
    {
        $this->assertSame($expected, Helper::sanitizePhone($input));
    }

    public static function validPhoneProvider(): array
    {
        return [
            'E.164'       => ['+380671234567', '+380671234567'],
            'без плюса'   => ['380671234567', '380671234567'],
            'локальний'   => ['0671234567', '0671234567'],
            'із пробілами' => ['  +380671234567  ', '+380671234567'],
        ];
    }

    /** @dataProvider invalidPhoneProvider */
    #[DataProvider('invalidPhoneProvider')]
    public function testNonPhoneIsRejected(string $input): void
    {
        $this->assertNull(Helper::sanitizePhone($input));
    }

    public static function invalidPhoneProvider(): array
    {
        return [
            'порожнє'          => [''],
            'закоротке'        => ['1'],
            'задовге'          => ['+' . str_repeat('9', 21)],
            'sip'              => ['sip:operator'],
            'спроба ін\'єкції' => ["'; DROP TABLE ok_orders; --"],
            'із дефісами'      => ['+38-067-123-45-67'],
        ];
    }

    /** @dataProvider dateTimeProvider */
    #[DataProvider('dateTimeProvider')]
    public function testDbDateTimeFormatIsRecognised(string $input, bool $expected): void
    {
        $this->assertSame($expected, Helper::isDbDateTime($input));
    }

    public static function dateTimeProvider(): array
    {
        return [
            'формат БД'     => ['2026-08-12 13:58:50', true],
            'із пробілами'  => [' 2026-08-12 13:58:50 ', true],
            'тільки дата'   => ['2026-08-12', false],
            'ISO з T'       => ['2026-08-12T13:58:50', false],
            'порожнє'       => ['', false],
            'сміття'        => ['NOW()', false],
        ];
    }
}
