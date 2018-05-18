<?php


class UpdateNote extends DataObject
{

    private static $fields_to_remove_in_the_cms_fields = [];

    private static $singular_name = 'Update Note';

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    private static $plural_name = 'Update Notes';

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    private static $db = array(
        'Note' => 'Varchar',
        'FutureReminderDate' => 'Date',
        'FutureReminderNote' => 'Varchar',
        'FutureReminderCompleted' => 'Boolean',
        'UpdateNoteRecordID' => 'Int',
        'UpdateNoteRecordClass' => 'Varchar(100)'
    );

    private static $has_one = array(
        'UpdatedBy' => 'Member',
        'UpdateNoteRecord' => 'DataObject'
    );

    private static $casting = array(
        'Title' => 'Varchar'
    );

    private static $indexes = array(
        'UpdateNoteRecordID' => true,
        'UpdateNoteRecordClass' => true,
        'FutureReminderNote' => true,
        'FutureReminderDate' => true
    );

    private static $default_sort = array(
        'IF("ClassName" = \'UpdateNoteToBeCompleted\' AND "FutureReminderCompleted" = 0, 0, 1)' => 'ASC',
        'FutureReminderDate' => 'ASC',
        'Created' => 'DESC'
    );

    // private static $required_fields = array();

    private static $summary_fields = array(
        'Created.Nice' => 'When',
        'LastEdited.Nice' => 'Last Edited',
        'UpdateNoteRecord.Title' => 'What',
        'Note' => 'Note',
        'UpdatedBy.Email' => 'Editor',
        'FutureReminderDate.Nice' => 'Future Reminder',
        'FutureReminderNote' => 'Reminder Note'
    );

    private static $field_labels = array(
        'Note' => 'Note',
        'FutureReminderDate' => 'Future Reminder Date',
        'FutureReminderNote' => 'Future Reminder Note',
        'UpdateNoteRecord' => 'What',
        'UpdateNoteRecordClass' => 'Record Type',
        'UpdateNoteRecordID' => 'Record ID',
        'UpdatedBy' => 'Editor'
    );

    /**
     *
     * PartialMatchFilter
     */
    private static $searchable_fields = array(
        'UpdatedByID'  => array(
            'field' => 'UpdateNoteSearchField',
            'filter' => 'ExactMatchFilter',
            'title' => 'Edited By'
        ),
        'Note' => 'PartialMatchFilter',
        'FutureReminderDate' => 'PartialMatchFilter',
        'FutureReminderNote' => 'PartialMatchFilter',
    );


    protected function getEditorsDropdown()
    {
        $admins = EcommerceRole::list_of_admins(true);
        return DropdownField::create(
            'UpdatedByID',
            'Edited By',
            $admins
        );
    }

    /**
     * e.g.
     *    $controller = singleton("MyModelAdmin");
     *    return $controller->Link().$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/edit";
      */
    public function CMSEditLink()
    {
        return Controller::join_links(
            Director::baseURL(),
            "/admin/updatenotes/".$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/edit"
        );
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->insertBefore(ReadonlyField::create('Created'), 'Note');
        $fields->insertBefore(ReadonlyField::create('LastEdited'), 'Note');
        $fieldLabels = $this->fieldLabels();
        $fields->removeByName('UpdateNoteRecordClass');
        $fields->removeByName('UpdateNoteRecordID');

        $otherFieldsToRemove = $this->Config()->get('fields_to_remove_in_the_cms_fields');
        foreach ($otherFieldsToRemove as $field) {
            $fields->removeByName($field);
            // code...
        }
        $fields->insertBefore(
            ReadonlyField::create(
                'Created'
            ),
            'Note'
        );
        $fields->insertBefore(
            ReadonlyField::create(
                'LastEdited'
            ),
            'Note'
        );
        if ($whoField = $fields->dataFieldByName('UpdatedByID')) {
            $fields->removeFieldFromTab('Root.Main', 'UpdatedByID');
            $who = $this->UpdatedBy();
            if ($who && $who->exists()) {
                if ($who->hasMethod('CMSEditLink')) {
                    $fields->addFieldToTab(
                        'Root.Main',
                        $whoField = ReadonlyField::create(
                            'UpdatedByLink',
                            $fieldLabels['UpdatedBy'],
                            '<h2><a href="'.$who->CMSEditLink().'" target="_blank">'.$who->getTitle().'</a></h2>'
                        )
                    );
                } else {
                    $fields->addFieldToTab(
                        'Root.Main',
                        $whoField = ReadonlyField::create(
                            'UpdatedByLink',
                            $fieldLabels['UpdatedBy'],
                            '<h2>'.$who->getTitle().'.</h2>'
                        )
                    );
                }
            } else {
                $fields->addFieldToTab(
                    'Root.Main',
                    $whoField = ReadonlyField::create(
                        'UpdatedByLink',
                        $fieldLabels['UpdatedBy'],
                        '<p class="message warning">no editor found</p>'
                    )
                );
            }
            $whoField->dontEscape = true;
        }

        if ($parent = $this->getParent()) {
            if ($parent->hasMethod('CMSEditLink')) {
                $fields->addFieldToTab(
                    'Root.Main',
                    $parentField = ReadonlyField::create(
                        'ParentLink',
                        $fieldLabels['UpdateNoteRecord'],
                        '<h2><a href="'.$parent->CMSEditLink().'" target="_blank">'.$parent->getTitle().'</a></h2>'
                    )
                );
            } else {
                $fields->addFieldToTab(
                    'Root.Main',
                    $parentField = ReadonlyField::create(
                        'ParentLink',
                        $fieldLabels['UpdateNoteRecord'],
                        '<h2>'.$parent->getTitle().'</h2>'
                    )
                );
            }
            $parentField->dontEscape = true;
        }
        return $fields;
    }

    public function getParentField($fieldLabels = null, $linkMethod = 'CMSEditLink')
    {
        if ($fieldLabels === null) {
            $fieldLabels = $this->fieldLabels();
        }
        $parentField = null;
        if ($parent = $this->owner->getParent()) {
            if ($parent->hasMethod($linkMethod)) {
                $parentField = ReadonlyField::create(
                    'ParentLink',
                    $fieldLabels['UpdateNoteRecord'],
                    '<p><a href="'.$parent->$linkMethod().'" target="_blank">'.$parent->getTitle().'</a></p>'
                );
            } else {
                $parentField = ReadonlyField::create(
                    'ParentLink',
                    $fieldLabels['UpdateNoteRecord'],
                    '<p>'.$parent->getTitle().'</p>'
                );
            }
            $parentField->dontEscape = true;
        } else {
            $parentField = LiteralField::create('ParentLink', '<p class="message bad">No parent found</p>');
        }
        return $parentField;
    }

    public function getParent()
    {
        if ($obj = $this->UpdateNoteRecord()) {
            return $obj;
        } else {
            $className = $this->UpdateNoteRecordClass;
            if ($className && class_exists($className)) {
                $id =  intval($this->UpdateNoteRecordID);
                if ($id) {
                    return $className::get()->byID($id);
                }
            }
        }
    }


    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->FutureReminderCompleted && $this instanceof UpdateNoteToBeCompleted) {
            $this->ClassName = 'UpdateNote';
        } elseif ($this->FutureReminderDate && ! $this instanceof UpdateNoteToBeCompleted) {
            $this->ClassName = 'UpdateNoteToBeCompleted';
        } elseif (!$this->FutureReminderDate && $this instanceof UpdateNoteToBeCompleted) {
            $this->ClassName = 'UpdateNote';
        }
    }
    /**
     * Creating Permissions
     * @return bool
     */
    public function canCreate($member = null)
    {
        return false;
    }

    /**
     * Editing Permissions
     * @return bool
     */
    public function canEdit($member = null)
    {
        return parent::canEdit();
    }

    /**
     * Deleting Permissions
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    public function getTitle()
    {
        $obj = $this->getParent();
        if ($obj && $obj->exists()) {
            $titleArray = array($this->Created, $obj->getTitle());
            if ($this->Note) {
                array_push($titleArray, $this->Note);
            }
            return implode(' - ', $titleArray);
        }
    }
}
