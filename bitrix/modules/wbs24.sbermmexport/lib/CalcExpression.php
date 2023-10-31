<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\SystemException;

class CalcExpression
{
    use Exception;

    // желательно заменить на более безопасный метод
    public function run($expression)
    {
        return eval('return '.$expression.';');
    }

    // отключено (т.к. может работать пока только для целых чисел)
    // https://ru.stackoverflow.com/questions/454598/
    public function _run($statement)
    {
        if (!is_string($statement)) {
            throw new SystemException('Wrong type', 1);
        }
        $calcQueue = array();
        $operStack = array();
        $operPriority = array(
            '(' => 0,
            ')' => 0,
            '+' => 1,
            '-' => 1,
            '*' => 2,
            '/' => 2,
        );
        $token = '';
        foreach (str_split($statement) as $char) {
            // Если цифра, то собираем из цифр число
            if ($char >= '0' && $char <= '9') {
                $token .= $char;
            } else {
                // Если число накопилось, сохраняем в очереди вычисления
                if (strlen($token)) {
                    array_push($calcQueue, $token);
                    $token = '';
                }
                // Если найденный символ - операция (он есть в списке приоритетов)
                if (isset($operPriority[$char])) {
                    if (')' == $char) {
                        // Если символ - закрывающая скобка, переносим операции из стека в очередь вычисления пока не встретим открывающую скобку
                        while (!empty($operStack)) {
                            $oper = array_pop($operStack);
                            if ('(' == $oper) {
                                break;
                            }
                            array_push($calcQueue, $oper);
                        }
                        if ('(' != $oper) {
                            // Упс! А открывающей-то не было
                            throw new SystemException('Unexpected ")"', 2);
                        }
                    } else {
                        // Встретили операцию кроме скобки. Переносим операции с меньшим приоритетом в очередь вычисления
                        while (!empty($operStack) && '(' != $char) {
                            $oper = array_pop($operStack);
                            if ($operPriority[$char] > $operPriority[$oper]) {
                                array_push($operStack, $oper);
                                break;
                            }
                            if ('(' != $oper) {
                                array_push($calcQueue, $oper);
                            }
                        }
                        // Кладем операцию на стек операций
                        array_push($operStack, $char);
                    }
                } elseif (strpos(' ', $char) !== FALSE) {
                    // Игнорируем пробелы (можно добавить что еще игнорируем)
                } else {
                    // Встретили что-то непонятное (мы так не договаривались)
                    throw new SystemException('Unexpected symbol "' . $char . '"', 3);
                }
            }

        }
        // Вроде все разобрали, но если остались циферки добавляем их в очередь вычисления
        if (strlen($token)) {
            array_push($calcQueue, $token);
            $token = '';
        }
        // ... и оставшиеся в стеке операции
        if (!empty($operStack)) {
            while ($oper = array_pop($operStack)) {
                if ('(' == $oper) {
                    // ... кроме открывающих скобок. Это верный признак отсутствующей закрывающей
                    throw new SystemException('Unexpected "("', 4);
                }
                array_push($calcQueue, $oper);
            }
        }
        $calcStack = array();
        // Теперь вычисляем все то, что напарсили
        foreach ($calcQueue as $token) {
            switch ($token) {
            case '+':
                $arg2 = array_pop($calcStack);
                $arg1 = array_pop($calcStack);
                array_push($calcStack, $arg1 + $arg2);
                break;
            case '-':
                $arg2 = array_pop($calcStack);
                $arg1 = array_pop($calcStack);
                array_push($calcStack, $arg1 - $arg2);
                break;
            case '*':
                $arg2 = array_pop($calcStack);
                $arg1 = array_pop($calcStack);
                array_push($calcStack, $arg1 * $arg2);
                break;
            case '/':
                $arg2 = array_pop($calcStack);
                $arg1 = array_pop($calcStack);
                array_push($calcStack, $arg1 / $arg2);
                break;
            default:
                array_push($calcStack, $token);
            }
        }

        return array_pop($calcStack);
    }
}
