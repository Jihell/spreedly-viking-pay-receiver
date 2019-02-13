<?php
/**
 * @licence Proprietary
 */
namespace Jihel\VikingPayReceiver\Bridge\Exception;

use Throwable;

/**
 * Class UnsupportedBankNumberException
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class UnsupportedBankNumberException extends \Exception
{
    public function __construct(string $bin, Throwable $previous = null)
    {
        parent::__construct(
            sprintf('The given bank number "%s" is not supported by our system.', $bin),
            '400.400',
            $previous
        );
    }
}
