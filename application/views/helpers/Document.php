<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

class Zend_View_Helper_Document extends Zend_View_Helper_Abstract
{
    public function document(array $document)
    {
        $html = ['<table class="document"><tbody>'];

        foreach ($document as $key => $value) {
            $html[] = '<tr>';

            $html[] = '<th>';
            $html[] = $this->view->escape($key);
            $html[] = '</th>';

            if (is_array($value)) {
                $html[] = '<td>';
                $html[] = $this->document($value);
            } else {
                $html[] = '<td class="value">';
                $html[] = $this->view->escape($value);
            }

            $html[] = '</td></tr>';
        }

        $html[] = '</tbody></table>';

        return implode("\n", $html);
    }
}
