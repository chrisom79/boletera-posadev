<?php

namespace Tests\Unit\DomainObjects;

use HiEvents\DomainObjects\Enums\QuestionTypeEnum;
use HiEvents\DomainObjects\QuestionDomainObject;
use Tests\TestCase;

class QuestionDomainObjectTest extends TestCase
{
    private function makeQuestion(string $type, array $options): QuestionDomainObject
    {
        return (new QuestionDomainObject())
            ->setType($type)
            ->setOptions($options);
    }

    public function testRadioAcceptsLiteralOption(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::RADIO->name, ['Yes', 'No', 'Otro']);

        $this->assertTrue($question->isAnswerValid('Yes'));
    }

    public function testRadioRejectsUnknownOption(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::RADIO->name, ['Yes', 'No', 'Otro']);

        $this->assertFalse($question->isAnswerValid('Maybe'));
    }

    public function testRadioAcceptsOtherOptionWithFreeText(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::RADIO->name, ['Yes', 'No', 'Otro']);

        $this->assertTrue($question->isAnswerValid('Otro: Some free text'));
    }

    public function testRadioRejectsOtherOptionWithEmptyFreeText(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::RADIO->name, ['Yes', 'No', 'Otro']);

        $this->assertFalse($question->isAnswerValid('Otro: '));
    }

    public function testRadioRejectsOtherPrefixWhenNoOtherOptionExists(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::RADIO->name, ['Yes', 'No']);

        $this->assertFalse($question->isAnswerValid('Otro: Some free text'));
    }

    public function testOtherDetectionIsCaseInsensitiveAndSupportsEnglish(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::RADIO->name, ['Yes', 'No', 'OTHER']);

        $this->assertTrue($question->isAnswerValid('OTHER: Some free text'));
    }

    public function testCheckboxAcceptsMixOfLiteralAndOtherOptions(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::CHECKBOX->name, ['JavaScript', 'Python', 'Otro']);

        $this->assertTrue($question->isAnswerValid(['JavaScript', 'Otro: COBOL']));
    }

    public function testCheckboxRejectsUnknownValueAlongsideValidOnes(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::CHECKBOX->name, ['JavaScript', 'Python', 'Otro']);

        $this->assertFalse($question->isAnswerValid(['JavaScript', 'Rust']));
    }

    public function testFreeTextQuestionTypeAcceptsAnyAnswer(): void
    {
        $question = $this->makeQuestion(QuestionTypeEnum::SINGLE_LINE_TEXT->name, []);

        $this->assertTrue($question->isAnswerValid('Anything at all'));
    }
}
