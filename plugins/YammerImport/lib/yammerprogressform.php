<?php

class YammerProgressForm extends Form
{
    /**
     * ID of the form
     *
     * @return string ID of the form
     */
    function id()
    {
        return 'yammer-progress';
    }

    /**
     * class of the form
     *
     * @return string class of the form
     */
    function formClass()
    {
        return 'form_settings';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    function action()
    {
        return common_local_url('yammeradminpanel');
    }

    /**
     * Data elements of the form
     *
     * @return void
     */
    function formData()
    {
        $runner = YammerRunner::init();

        $userCount = $runner->countUsers();
        $groupCount = $runner->countGroups();
        $fetchedCount = $runner->countFetchedNotices();
        $savedCount = $runner->countSavedNotices();

        $labels = array(
            'init' => array(
                'label' => _m("Initialize"),
                'progress' => _m('No import running'),
                'complete' => _m('Initiated Yammer server connection...'),
            ),
            'requesting-auth' => array(
                'label' => _m('Connect to Yammer'),
                'progress' => _m('Awaiting authorization...'),
                'complete' => _m('Connected.'),
            ),
            'import-users' => array(
                'label' => _m('Import user accounts'),
                'progress' => sprintf(_m("Importing %d user...", "Importing %d users...", $userCount), $userCount),
                'complete' => sprintf(_m("Imported %d user.", "Imported %d users.", $userCount), $userCount),
            ),
            'import-groups' => array(
                'label' => _m('Import user groups'),
                'progress' => sprintf(_m("Importing %d group...", "Importing %d groups...", $groupCount), $groupCount),
                'complete' => sprintf(_m("Imported %d group.", "Imported %d groups.", $groupCount), $groupCount),
            ),
            'fetch-messages' => array(
                'label' => _m('Prepare public notices for import'),
                'progress' => sprintf(_m("Preparing %d notice...", "Preparing %d notices...", $fetchedCount), $fetchedCount),
                'complete' => sprintf(_m("Prepared %d notice.", "Prepared %d notices.", $fetchedCount), $fetchedCount),
            ),
            'save-messages' => array(
                'label' => _m('Import public notices'),
                'progress' => sprintf(_m("Importing %d notice...", "Importing %d notices...", $savedCount), $savedCount),
                'complete' => sprintf(_m("Imported %d notice.", "Imported %d notices.", $savedCount), $savedCount),
            ),
            'done' => array(
                'label' => _m('Done'),
                'progress' => sprintf(_m("Import is complete!")),
                'complete' => sprintf(_m("Import is complete!")),
            )
        );
        $steps = array_keys($labels);
        $currentStep = array_search($runner->state(), $steps);

        $this->out->elementStart('fieldset', array('class' => 'yammer-import'));
        $this->out->element('legend', array(), _m('Import status'));
        foreach ($steps as $step => $state) {
            if ($state == 'init') {
                // Don't show 'init', it's boring.
                continue;
            }
            if ($step < $currentStep) {
                // This step is done
                $this->progressBar($state,
                                   'complete',
                                   $labels[$state]['label'],
                                   $labels[$state]['complete']);
            } else if ($step == $currentStep) {
                // This step is in progress
                $this->progressBar($state,
                                   'progress',
                                   $labels[$state]['label'],
                                   $labels[$state]['progress']);
            } else {
                // This step has not yet been done.
                $this->progressBar($state,
                                   'waiting',
                                   $labels[$state]['label'],
                                   _m("Waiting..."));
            }
        }
        $this->out->elementEnd('fieldset');
    }

    private function progressBar($state, $class, $label, $status)
    {
        // @fixme prettify ;)
        $this->out->elementStart('div', array('class' => "import-step import-step-$state $class"));
        $this->out->element('div', array('class' => 'import-label'), $label);
        $this->out->element('div', array('class' => 'import-status'), $status);
        $this->out->elementEnd('div');
    }

}
