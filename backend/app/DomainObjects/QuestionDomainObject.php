<?php

namespace HiEvents\DomainObjects;

use HiEvents\DomainObjects\Enums\QuestionTypeEnum;
use Illuminate\Support\Collection;

class QuestionDomainObject extends Generated\QuestionDomainObjectAbstract
{
    public ?Collection $products = null;

    public function setProducts(?Collection $products): QuestionDomainObject
    {
        $this->products = $products;
        return $this;
    }

    public function getProducts(): ?Collection
    {
        return $this->products;
    }

    public function isPreDefinedChoice(): bool
    {
        return in_array($this->getType(), [
            QuestionTypeEnum::MULTI_SELECT_DROPDOWN->name,
            QuestionTypeEnum::CHECKBOX->name,
            QuestionTypeEnum::RADIO->name,
            QuestionTypeEnum::DROPDOWN->name,
        ], true);
    }

    public function setOptions(array|string|null $options): self
    {
        if (is_array($options)) {
            $options = array_filter(array_unique($options));
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Options matching one of these (case-insensitive, trimmed) are treated as an
     * "other" choice: an answer of "<option>: <free text>" is accepted alongside
     * the literal option, so radio/checkbox questions can collect a free-text
     * value when the respondent picks "Otro"/"Other" without needing a schema
     * change to mark which option that is.
     */
    private const OTHER_OPTION_LABELS = ['otro', 'otra', 'other'];

    private function isOtherOptionAnswer(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        foreach ($this->getOptions() ?? [] as $option) {
            if (in_array(mb_strtolower(trim($option)), self::OTHER_OPTION_LABELS, true)
                && str_starts_with($value, $option . ': ')
                && trim(substr($value, strlen($option) + 2)) !== '') {
                return true;
            }
        }

        return false;
    }

    public function isAnswerValid(mixed $answer): bool
    {
        if (!isset($answer)) {
            return false;
        }

        if (!$this->isPreDefinedChoice()) {
            return true;
        }

        if (is_string($answer)) {
            return in_array($answer, $this->getOptions(), true) || $this->isOtherOptionAnswer($answer);
        }

        foreach ((array)$answer as $value) {
            if (!in_array($value, $this->getOptions(), true) && !$this->isOtherOptionAnswer($value)) {
                return false;
            }
        }

        return true;
    }
}
