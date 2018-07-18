<?php

namespace Kingsquare\Parser\Banking\Mt940\Engine;

use Kingsquare\Parser\Banking\Mt940\Engine;

/**
 * @author Kingsquare (source@kingsquare.nl)
 * @license http://opensource.org/licenses/MIT MIT
 */
class Mollie extends Engine
{
    /**
     * returns the name of the bank
     * @return string
     */
    protected function parseStatementBank()
    {
        return 'MOLLIE';
    }

    /**
     * Overloaded: Should not remove header with Mollie
     * @inheritdoc
     */
    protected function parseStatementData()
    {
        $results = preg_split(
            '/(^:20:|^-X{,3}$|\Z)/sm',
            $this->getRawData(),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
 //       array_shift($results); // remove the header
        return $results;
    }

    /**
     * Overloaded: Mollie has different way of storing account name
     * @inheritdoc
     */
    protected function parseTransactionAccountName()
    {
        $results = [];
       //echo $this->getCurrentTransactionData();
        // SEPA MT940 Structured
       //
        //(preg_match('/:86:(?:.*?)NAME\/(.+?)\//im'
        if (preg_match('/:86:(?:.*?)NAME\/((.|\n|\r)+?)\//im', $this->getCurrentTransactionData(), $results)
            && !empty($results[1])
        ) {
            return $this->sanitizeAccountName($results[1]);
        }

        return '';
    }

    /**
     * Overloaded: Mollie has different way of storing account info
     * @inheritdoc
     */
    protected function parseTransactionAccount()
    {
        //echo $this->getCurrentTransactionData();

        $results = [];
        if (preg_match('/:86:(?:.*?)IBAN\/(.+?)\//im', $this->getCurrentTransactionData(), $results)
            && !empty($results[1])
        ) {
          //  echo 'founda account '. $results[1];
            return $this->sanitizeAccount($results[1]);
        }
        return '';
    }

    /**
     * Overloaded: Mollie encapsulates the description with /REMI/ for SEPA
     * @inheritdoc
     */
    protected function sanitizeDescription($string)
    {
        $description = parent::sanitizeDescription($string);
        $sanitizedDescription ="";
        if (strpos($description, '/REMI/') !== false
            && preg_match('/\/REMI\/(.+?)\//s', $description, $results) && !empty($results[1])
        ) {
        //    echo $results[1];
            $sanitizedDescription = $results[1];
        }
        if (strpos($description, '/EREF/') !== false
            && preg_match('/\/EREF\/((.+|\n)+)/s', $description, $results) && !empty($results[1])
        ) {
        //    echo $results[1];
          //  echo "\n".strpos($results[1],'NOTPROVIDED')."\n";
            if(!(strpos($results[1],'NOTPROVIDED')>-1)) {
                $sanitizedDescription = $sanitizedDescription . " - " . $results[1];
            }
        }
        return $sanitizedDescription;
    }

    /**
     * Overloaded: Is applicable if first line has INGB.
     *
     * {@inheritdoc}
     */
    public static function isApplicable($string)
    {
        $secondline = strtok("\r\n\t");
        return strpos($secondline, ':25:NL30ABNA0524590958') !== false;
    }
}
