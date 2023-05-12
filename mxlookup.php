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

            // Check if MX records are retrieved
            if (is_array($mx_records)) {
                // Filter out any MX records containing the word "relay"
                $mx_records = array_filter($mx_records, function ($record) {
                    return strpos($record['target'], 'relay') === false;
                });

                // Sort the remaining MX records by priority (lower is better)
                usort($mx_records, function ($a, $b) {
                    return $a['pri'] - $b['pri'];
                });

                // Get the first (i.e., lowest priority) MX record
                $mx_record = reset($mx_records);

                // Set the mxlookup_host configuration value to the target of the selected MX record
                $this->rc->config->set('mxlookup_host', $mx_record['target']);
            }
        }

        // Perform the A record lookup for the imap_host
        $imap_host = $this->rc->config->get('mxlookup_host');
        $imap_host_ip = gethostbyname($imap_host);

        // Read the whitelist file and extract the IP addresses into an array
        $whitelist_file = __DIR__ . '/whitelist.txt';
        $whitelisted_ips = file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Check if the resolved IP address is in the whitelist
        if (!in_array($imap_host_ip, $whitelisted_ips)) {
            // Prevent login by aborting the authentication process
            $args['abort'] = true;
        }

        // Set the IMAP host value to the mxlookup_host configuration value
        $args['host'] = $this->rc->config->get('mxlookup_host');

        return $args;
    }
}

?>
