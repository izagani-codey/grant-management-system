<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;

class DynamicFormFields extends Component
{
    public array $fields;
    public string $prefix;
    public array $values;
    public ViewErrorBag $errorBag;

    public function __construct(array $fields, string $prefix = 'dynamic_fields', array $values = [])
    {
        $this->fields = $fields;
        $this->prefix = $prefix;
        $this->values = $values;
        $this->errorBag = session('errors') instanceof ViewErrorBag
            ? session('errors')
            : new ViewErrorBag();
    }

    public function render(): View|Closure|string
    {
        return view('components.dynamic-form-fields');
    }

    /**
     * Get the input name for a field
     */
    public function getInputName(string $fieldName): string
    {
        return "{$this->prefix}[{$fieldName}]";
    }

    /**
     * Get the old value or default for a field
     */
    public function getValue(string $fieldName, $default = null)
    {
        return old("{$this->prefix}.{$fieldName}", $this->values[$fieldName] ?? $default);
    }

    /**
     * Check if field has error
     */
    public function hasError(string $fieldName): bool
    {
        return $this->errorBag->has("{$this->prefix}.{$fieldName}");
    }

    /**
     * Get error message for field
     */
    public function getError(string $fieldName): ?string
    {
        return $this->errorBag->first("{$this->prefix}.{$fieldName}");
    }
}
