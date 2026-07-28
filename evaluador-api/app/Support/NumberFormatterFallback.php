<?php

if (!class_exists('NumberFormatter', false)) {
    class NumberFormatter
    {
        public const SPELLOUT = 5;

        public function __construct(
            private string $locale = 'es',
            private int $style = self::SPELLOUT,
        ) {}

        public function format(int|float $num, int $type = 0): string|false
        {
            if ($this->style !== self::SPELLOUT) {
                return false;
            }

            $numero = (int) round($num);

            if ($numero === 0) {
                return 'cero';
            }

            if ($numero < 0) {
                return 'menos ' . $this->numeroATexto(abs($numero));
            }

            return $this->numeroATexto($numero);
        }

        private function numeroATexto(int $numero): string
        {
            if ($numero < 1000) {
                return $this->centenasATexto($numero);
            }

            if ($numero < 1000000) {
                $miles = intdiv($numero, 1000);
                $resto = $numero % 1000;
                $texto = $miles === 1 ? 'mil' : $this->centenasATexto($miles) . ' mil';

                return trim($texto . ($resto ? ' ' . $this->centenasATexto($resto) : ''));
            }

            $millones = intdiv($numero, 1000000);
            $resto = $numero % 1000000;
            $texto = $millones === 1 ? 'un millón' : $this->numeroATexto($millones) . ' millones';

            return trim($texto . ($resto ? ' ' . $this->numeroATexto($resto) : ''));
        }

        private function centenasATexto(int $numero): string
        {
            $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
            $especiales = [
                10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince',
                16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve',
                20 => 'veinte', 21 => 'veintiuno', 22 => 'veintidós', 23 => 'veintitrés', 24 => 'veinticuatro',
                25 => 'veinticinco', 26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve',
            ];
            $decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
            $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

            if ($numero === 0) {
                return '';
            }

            if ($numero === 100) {
                return 'cien';
            }

            if ($numero < 10) {
                return $unidades[$numero];
            }

            if ($numero < 30) {
                return $especiales[$numero];
            }

            if ($numero < 100) {
                $decena = intdiv($numero, 10);
                $unidad = $numero % 10;

                return $decenas[$decena] . ($unidad ? ' y ' . $unidades[$unidad] : '');
            }

            $centena = intdiv($numero, 100);
            $resto = $numero % 100;

            return $centenas[$centena] . ($resto ? ' ' . $this->centenasATexto($resto) : '');
        }
    }
}
