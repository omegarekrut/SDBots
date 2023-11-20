<?php

namespace App\Services;

class MarkdownFormatter
{
    public function escape(string $text): string
    {
        $search = ['_', '*', '[', ']', '(', ')', '~', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $replace = array_map(fn($char) => "\\$char", $search);

        return str_replace($search, $replace, $text);
    }
}
