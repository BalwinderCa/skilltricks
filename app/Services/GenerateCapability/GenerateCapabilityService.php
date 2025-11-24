<?php

namespace App\Services\GenerateCapability;

class GenerateCapabilityService
{

    /**
     * Checking Content Generating Capabilities
     *
     * Term 1 : When Customer Balance is 0 means return false.
     * Term 2 : When Admin Limit has 0 means no limitations and also Generate limit has 0 means no limitations defined from the front-end means return true.
     * Term 3 : When Admin Limit has 0 means no limitations and Generate limit has value means generate limit shouldn't be greater than customer balance. means true. [Simple Word : generate limit should be less than or equal customer balance]
     * Term 4 : When Admin Limit has value and Generate limit has 0 means customer balance should be greater than admin limit . Ex. customerBalance>= adminLimit means true either false.
     * Term 5 : When Generate Limit has value means customer balance should be greater than or equal generate limit. means true either false.
     *
     * */
    public function checkGenerateCapability(int $generateLimit)
    {
        $isAllowed = false;

        (int) $adminLimit  = getSetting("default_max_result_length_blog_wizard");
        (int) $customerBal = getCustomerBalance();

        // Term 1 :
        if($customerBal <= 0){
            return $isAllowed;
        }
        // Term 2
        elseif($adminLimit <= 0 && $generateLimit <= 0){
            $isAllowed  = true;
        }
        // Term 3
        elseif($adminLimit <= 0 && $generateLimit>0 && $generateLimit <= $customerBal) {
            $isAllowed  = true;
        }
        // Term 4
        elseif($adminLimit > 0 && $generateLimit <= 0 && $adminLimit <= $customerBal) {
            $isAllowed  = true;
        }
        // Term 5
        elseif($customerBal >= $generateLimit){
            $isAllowed = true;
        }

        return $isAllowed;
    }
}
