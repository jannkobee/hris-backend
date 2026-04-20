<?php

namespace App\Repository\Employee;

use App\Repository\Base\BaseRepositoryInterface;

interface EmployeeRepositoryInterface extends BaseRepositoryInterface
{
    public function generateEmployeeNo();

    public function getEmployeeNumberSettings();

    public function updateEmployeeNumberSettings(array $data);

    public function reformatEmployeeNumbers();
}
