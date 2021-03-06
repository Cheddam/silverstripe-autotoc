<?php

class Autotoc extends Extension
{
    private $_tocifier;


    private static function _convertNode($node)
    {
        $data = new ArrayData(array(
            'Id'    => $node['id'],
            'Title' => $node['title']
        ));

        if (isset($node['children'])) {
            $data->setField('Children', self::_convertChildren($node['children']));
        }

        return $data;
    }

    private static function _convertChildren($children)
    {
        $list = new ArrayList;

        foreach ($children as $child) {
            $list->push(self::_convertNode($child));
        }

        return $list;
    }

    private function _contentField()
    {
        $field = $this->owner->config()->get('content_field');
        return $field ? $field : 'Content';
    }

    /**
     * Provide content_field customization on a class basis.
     *
     * Override the default setOwner() method so, when valorized, I can
     * enhance the (possibly custom) content field with anchors. I did
     * not find a better way to override a field other than directly
     * substituting it with setField().
     *
     * @param Object $owner      The owner instance
     * @param string $base_class The name of the base class this
     *                           extension is applied to
     */
    public function setOwner($owner, $base_class = null)
    {
        parent::setOwner($owner, $base_class);

        if ($owner) {
            $tocifier = $this->_getTocifier();
            $content  = $tocifier ? $tocifier->getHtml() : $this->_getHtml();
            $owner->setField($this->_contentField(), $content);
        }
    }

    private function _getHtml()
    {
        $c = $this->owner;
        $model = $c->customisedObject ? $c->customisedObject : $c->data();
        if (! $model) {
            return null;
        }

        $field = $this->_contentField();
        if (! $model->hasField($field)) {
            return null;
        }

        return $model->obj($field)->forTemplate();
    }

    private function _getTocifier()
    {
        if (is_null($this->_tocifier)) {
            $tocifier = new Tocifier($this->_getHtml());
            $this->_tocifier = $tocifier->process() ? $tocifier : false;
        }

        return $this->_tocifier;
    }

    public function getAutotoc()
    {
        $tocifier = $this->_getTocifier();
        if (! $tocifier) {
            return null;
        }

        $toc = $tocifier->getTOC();
        if (empty($toc)) {
            return '';
        }

        return new ArrayData(array(
            'Children' => self::_convertChildren($toc)
        ));
    }

    public function getBodyAutotoc()
    {
        return ' data-spy="scroll" data-target=".toc"';
    }
};
