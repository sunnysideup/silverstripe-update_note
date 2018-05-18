<?php



class UpdateNoteExtension extends DataExtension
{
    private static $has_many = array(
        'UpdateNotes' => 'UpdateNote'
    );

    private static $seconds_grace = 600;

    private static $latest_limit = 600;

    private static $_run_once_only = array();

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $id = $this->owner->ID;
        $class = $this->owner->ClassName;
        $userID = Member::currentUserID();
        $filter = array(
            'UpdateNoteRecordID' => $id,
            'UpdateNoteRecordClass' => $class,
            'UpdatedByID' => $userID
        );
        $key = implode('_', $filter);
        if (! isset(self::$_run_once_only[$key])) {
            self::$_run_once_only[$key] = true;
            $now = SS_Datetime::now()->Rfc2822();
            $gracePointInTime = strtotime($now) - Config::inst()->get('UpdateNoteExtension', 'seconds_grace');
            $log = DataObject::get_one(
                'UpdateNote',
                $filter,
                $cacheDataObjectGetOne = false,
                array('Created' => 'DESC')
            );
            if ($log) {
                $editPointInTime = strtotime($log->Created);
                debug::log(Date('l jS \of F Y h:i:s A', $editPointInTime).'...'.Date('l jS \of F Y h:i:s A', $gracePointInTime));
                if ($editPointInTime < $gracePointInTime) {
                    $log = null;
                }
            }
            if ($log && $log->exists()) {
                //already exists ...
            } else {
                $log = UpdateNote::create($filter);
            }
            $log->write();
        }
    }

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $name = Injector::inst()->get('UpdateNote')->i18n_plural_name();
        $fields->addFieldToTab(
            'Root.'.$name,
            GridField::create(
                'UpdateNotes',
                $name,
                $this->owner->UpdateNotes(),
                GridFieldConfig_RecordEditor::create()
            )
        );

        return $fields;
    }

    /**
     * Event handler called before deleting from the database.
     */
    public function onBeforeDelete()
    {
        foreach ($this->owner->UpdateNotes() as $note) {
            $note->delete();
        }
    }


    public function LatestUpdateNotes()
    {
        return $this->owner->UpdateNotes()
            ->limit(Config::inst()->get('xx', 'latest_limit'));
    }
}
