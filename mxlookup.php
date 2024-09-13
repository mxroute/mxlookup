<?php

class mxlookup extends rcube_plugin
{
    private $rc;

    public function init()
    {
        $this->rc = rcube::get_instance();
        $this->add_hook('authenticate', array($this, 'modify_imap_host'));
        $this->rc->config->set('mxlookup_host', '');
    }

    public function modify_imap_host($args)
    {
        if (empty($this->rc->config->get('mxlookup_host'))) {
            $login = $args['user'];

            // Validate email format
            if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $args['abort'] = true;
                return $args;
            }

            list($user, $domain) = explode('@', $login);

            // Validate domain
            if (!$this->is_valid_domain($domain)) {
                $args['abort'] = true;
                return $args;
            }

            $mx_records = @dns_get_record($domain, DNS_MX);

            if (is_array($mx_records)) {
                $mx_records = array_filter($mx_records, function ($record) {
                    return strpos(strtolower($record['target']), 'relay') === false;
                });

                usort($mx_records, function ($a, $b) {
                    return $a['pri'] - $b['pri'];
                });

                $mx_record = reset($mx_records);

                if ($mx_record && isset($mx_record['target'])) {
                    $this->rc->config->set('mxlookup_host', $mx_record['target']);
                }
            }
        }

        $imap_host = $this->rc->config->get('mxlookup_host');
        
        // Validate IMAP host
        if (!$this->is_valid_domain($imap_host)) {
            $args['abort'] = true;
            return $args;
        }

        $imap_host_ip = gethostbyname($imap_host);

        // Validate IP address
        if (!filter_var($imap_host_ip, FILTER_VALIDATE_IP)) {
            $args['abort'] = true;
            return $args;
        }

        $whitelist_file = __DIR__ . '/whitelist.txt';
        $whitelisted_ips = $this->load_whitelist($whitelist_file);

        if (!in_array($imap_host_ip, $whitelisted_ips)) {
            $args['abort'] = true;
        } else {
            $args['host'] = $imap_host;
        }

        return $args;
    }

    private function is_valid_domain($domain)
    {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain) //valid chars check
                && preg_match("/^.{1,253}$/", $domain) //overall length check
                && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain)); //length of each label
    }

    private function load_whitelist($file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            return array();
        }
        $ips = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_filter($ips, function($ip) {
            return filter_var($ip, FILTER_VALIDATE_IP);
        });
    }
}
