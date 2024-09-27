<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ExpenseTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expense-tracker {action} {--id=} {--description=} {--amount=} {--month=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A simple expense tracker to manage your finances.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $id = $this->option('id');
        $description = $this->option('description');
        $amount = $this->option('amount');
        $month = $this->option('month');

        switch ($action) {
            case 'add':
                $this->addExpense($description, $amount);
                break;
            case 'update':
                $this->updateExpense($id, $description, $amount);
                break;
            case 'delete':
                $this->deleteExpense($id);
                break;
            case 'list':
                $this->listExpenses();
                break;
            case 'summary':
                $this->summaryExpenses($month);
                break;
            default:
                $this->error('Invalid action.');
                break;
        }
    }

    private function addExpense($description, $amount)
    {
        if (!$description || !$amount) {
            $this->error('Both description and amount are required to add an expense.');
            return;
        }

        $expenses = $this->getExpenses();
        $id = count($expenses) + 1;

        $expenses[] = [
            'id' => $id,
            'description' => $description,
            'amount' => $amount,
            'date' => Carbon::now()->toDateString(),
        ];

        $this->saveExpenses($expenses);
        $this->info("Expense added successfully (ID: $id)");
    }

    private function updateExpense($id, $description, $amount)
    {
        $expenses = $this->getExpenses();

        foreach ($expenses as &$expense) {
            if ($expense['id'] == $id) {
                $expense['description'] = $description ?? $expense['description'];
                $expense['amount'] = $amount ?? $expense['amount'];
                $expense['updated_at'] = Carbon::now()->toDateString();
                $this->saveExpenses($expenses);
                $this->info('Expense updated successfully.');
                return;
            }
        }

        $this->error('Expense not found.');
    }

    private function deleteExpense($id)
    {
        $expenses = $this->getExpenses();
        $expenses = array_filter($expenses, fn($expense) => $expense['id'] != $id);

        $this->saveExpenses($expenses);
        $this->info('Expense deleted successfully.');
    }

    private function listExpenses()
    {
        $expenses = $this->getExpenses();
        if (empty($expenses)) {
            $this->info('No expenses found.');
            return;
        }

        // Crear una nueva matriz para la tabla con los campos correctos
        $tableData = array_map(function ($expense) {
            return [
                'ID' => $expense['id'],
                'Date' => $expense['date'],
                'Description' => $expense['description'],
                'Amount' => '$' . number_format($expense['amount'], 2), // Formatear el monto
            ];
        }, $expenses);

        // Mostrar la tabla
        $this->table(['ID', 'Date', 'Description', 'Amount'], $tableData);
    }


    private function summaryExpenses($month = null)
    {
        $expenses = $this->getExpenses();
        $total = 0;

        if ($month) {
            $filteredExpenses = array_filter($expenses, function ($expense) use ($month) {
                return Carbon::parse($expense['date'])->month == $month;
            });

            foreach ($filteredExpenses as $expense) {
                $total += $expense['amount'];
            }

            $this->info("Total expenses for month $month: $$total");
        } else {
            foreach ($expenses as $expense) {
                $total += $expense['amount'];
            }

            $this->info("Total expenses: $$total");
        }
    }

    private function getExpenses()
    {
        if (!Storage::exists('expenses.json')) {
            return [];
        }

        $expenses = Storage::get('expenses.json');
        return json_decode($expenses, true) ?? [];
    }

    private function saveExpenses($expenses)
    {
        Storage::put('expenses.json', json_encode($expenses, JSON_PRETTY_PRINT));
    }
}
