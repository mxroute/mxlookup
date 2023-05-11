<?php

class mxlookup extends rcube_plugin
{
    private $rc;

    public function init()
    {
        // Define the default configuration values for the plugin
        $this->rc = rcube::get_instance();
        $this->add_hook('authenticate', array($this, 'modify_imap_host'));
        $this->rc->config->set('mxlookup_host', '');
    }

    public function modify_imap_host($args)
    {
        // Check if the mxlookup_host configuration value is empty
        if (empty($this->rc->config->get('mxlookup_host'))) {
            // Get the user's login name from the login form
            $login = $args['user'];

            // Split the login name into username and domain parts
            list($user, $domain) = explode('@', $login);

            // Get the MX records for the domain
            $mx_records = dns_get_record($domain, DNS_MX);

            // Filter out any MX records containing the word "relay"
            $mx_records = array_filter($mx_records, function ($record) {
                return strpos($record['target'], 'relay') === false;
            });

            // Sort the remaining MX records by priority (lower is better)
            usort($mx_records, function ($a, $b) {
                return $a['pri'] - $b['pri'];
            });

            // Get the first (i.e. lowest priority) MX record
            $mx_record = reset($mx_records);

            // Set the mxlookup_host configuration value to the target of the selected MX record
            $this->rc->config->set('mxlookup_host', $mx_record['target']);
        }

        // Set the IMAP host value to the mxlookup_host configuration value
        $args['host'] = $this->rc->config->get('mxlookup_host');

        return $args;
    }
}

?>
