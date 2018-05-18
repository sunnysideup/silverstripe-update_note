<?php



class UpdateNoteSearchField extends DropdownField
{
    public function getSource()
    {
        $members = UpdateNote::get()->column('UpdatedByID');
        $memberArray = Member::get()->filter(array("ID" => $members))->map()->toArray();
        foreach ($memberArray as $id => $title) {
            if (! $title) {
                unset($memberArray[$id]);
            }
        }
        return array(''=> _t('UpdateNoteSearchField.ANYONE', '- Anyone -')) + $memberArray;
    }
}
