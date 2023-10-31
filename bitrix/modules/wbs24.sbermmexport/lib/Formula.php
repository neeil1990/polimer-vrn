<?php
namespace Wbs24\Sbermmexport;

class Formula
{
    protected $formula = '{PRICE}';
    protected $marks = [
        'PRICE',
    ];
    protected $allowedSymbols = [
        '.',
        '*',
        '/',
        '+',
        '-',
        '(',
        ')',
        ' ',
    ];

    protected $CalcExpression;

    public function __construct($objects = [])
    {
        $this->CalcExpression = $objects['CalcExpression'] ?? new CalcExpression();
    }

    public function setMarks($marks)
    {
        $this->marks = $marks;
    }

    public function setFormula($formula)
    {
        $this->formula = $this->cleanFormula($formula);
    }

    public function calc($fields)
    {
        $expression = $this->getExpressionByFormula($fields);

        return $this->CalcExpression->run($expression);
    }

    public function cleanFormula($formula)
    {
        $allowedSymbols = $this->allowedSymbols;
        $allowedMarks = $this->marks;

        // убираем все #
        $cleanedFormula = str_replace('#', '', $formula);
        // меняем все метки на #, запоминая метки в массив $replacedMarks
        $replacedMarks = [];
        $key = 0;
        foreach ($allowedMarks as $mark) {
            $i = 0;
            while (strpos($cleanedFormula, '{'.$mark.'}') !== false) {
                $i++;
                $cleanedFormulaForNextStep = preg_replace('/\{'.$mark.'\}/', ' #'.$key.'# ', $cleanedFormula, 1);
                if ($cleanedFormulaForNextStep != $cleanedFormula) {
                    $replacedMarks[$key] = $mark;
                    $cleanedFormula = $cleanedFormulaForNextStep;
                    $key++;
                }
                if ($i > 10) break; // защита от зависания
            }
        }
        // очищаем строку от невалидных символов (кроме #)
        // (запятую меняем на точку)
        $cleanedFormula = str_replace(',', '.', $cleanedFormula);
        for ($i = 0; $i < strlen($cleanedFormula); $i++) {
            $symbol = $cleanedFormula[$i];
            if (
                $symbol != '#'
                && !in_array($symbol, $allowedSymbols)
                && !is_numeric($symbol)
            ) {
                $cleanedFormula[$i] = '_';
            }
        }
        $cleanedFormula = str_replace('_', '', $cleanedFormula);
        // вернуть метки на место
        foreach ($replacedMarks as $key => $mark) {
            $cleanedFormula = preg_replace('/ \#'.$key.'\# /', '{'.$mark.'}', $cleanedFormula, 1);
        }

        return trim($cleanedFormula);
    }

    protected function getExpressionByFormula($fields)
    {
        $expression = $this->formula;
        foreach ($fields as $code => $value) {
            $expression = str_replace('{'.$code.'}', $value, $expression);
        }

        return $expression;
    }
}
