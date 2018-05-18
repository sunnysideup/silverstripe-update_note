<?php


class UpdateNoteToBeCompleted extends UpdateNote
{
    private static $singular_name = 'Reminder';

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    private static $plural_name = 'Reminders';

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }
}
