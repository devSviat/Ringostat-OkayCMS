<?php

namespace Modules\Sviat\Ringostat;

use Okay\Modules\Sviat\Ringostat\Helpers\RingostatPhoneFormatHelper as Helper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Номер із картки замовлення менеджер натискає, щоб подзвонити. `isPhone()`
 * вирішує, чи буде посилання `tel:`, а `formatDisplay()` — що менеджер побачить.
 */
class RingostatPhoneFormatHelperTest extends TestCase
{
    /** @dataProvider ukrainianNumberProvider */
    #[DataProvider('ukrainianNumberProvider')]
    public function testUkrainianNumbersGetASpacedDisplayForm(string $input): void
    {
        $this->assertSame('+380 50 123 45 67', Helper::formatDisplay($input));
    }

    public static function ukrainianNumberProvider(): array
    {
        return [
            'E.164'            => ['+380501234567'],
            'без плюса'        => ['380501234567'],
            'локальний'        => ['0501234567'],
            'із пробілами'     => [' +38 (050) 123-45-67 '],
        ];
    }

    /** @dataProvider ukrainianNumberProvider */
    #[DataProvider('ukrainianNumberProvider')]
    public function testUkrainianNumbersAreDialable(string $input): void
    {
        $this->assertTrue(Helper::isPhone($input));
    }

    /** @dataProvider notAPhoneProvider */
    #[DataProvider('notAPhoneProvider')]
    public function testNonPhonesAreNotDialable(string $input): void
    {
        $this->assertFalse(Helper::isPhone($input));
    }

    public static function notAPhoneProvider(): array
    {
        return [
            'імʼя'             => ['Петро'],
            'літери з цифрами' => ['050ABC4567'],
            'закоротко'        => ['12345'],
            'задовго'          => ['3805012345678'],
            'порожній рядок'   => [''],
            'чужий код країни' => ['+48501234567'],
        ];
    }

    /** Те, що не розпізналось як номер, віддається без змін. */
    public function testUnrecognisedInputIsReturnedAsIs(): void
    {
        $this->assertSame('Петро', Helper::formatDisplay('Петро'));
        $this->assertSame('+48 501 234 567', Helper::formatDisplay('+48 501 234 567'));
    }

    public function testDigitsAreExtractedForComparison(): void
    {
        $this->assertSame('380501234567', Helper::getDigits('+38 (050) 123-45-67'));
        $this->assertSame('', Helper::getDigits('немає цифр'));
    }

    /**
     * Два записи того самого номера мають зводитись до одного рядка цифр —
     * інакше дзвінок не звʼяжеться з замовленням.
     */
    public function testTheSameNumberInAnyNotationGivesTheSameDigits(): void
    {
        $this->assertSame(
            Helper::getDigits('+380501234567'),
            Helper::getDigits('38 050 123 45 67')
        );
    }
}
