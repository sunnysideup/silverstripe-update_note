<?php


class UpdateNoteModelAdmin extends ModelAdmin
{
    public $showImportForm = false;

    private static $managed_models = array('UpdateNote', 'UpdateNoteToBeCompleted');

    private static $url_segment = 'updatenotes';

    private static $menu_title = 'Update Log';

    private static $menu_icon = 'update_note/images/treeicons/UpdateNoteModelAdmin.png';

    /**
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        if (is_subclass_of($this->modelClass, 'UpdateNoteToBeCompleted') || $this->modelClass === 'UpdateNoteToBeCompleted') {
            $list = $list->filter(array('FutureReminderCompleted' => 0));
        }
        return $list;
    }
}
