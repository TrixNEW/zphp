<?php
// a DOM or SimpleXML object keeps what it points at alive: its document while
// any node of it is wrapped, a detached node while its wrapper lives, and a
// node always comes back as the same object. freeing a tree never takes a
// node another object still holds

$doc = new DOMDocument;
$doc->loadXML('<r><a><b>one</b><c/></a><d/></r>');
$root = $doc->documentElement;
var_dump($root->firstChild === $root->firstChild);
var_dump($root->firstChild->parentNode === $root);

// the document outlives its wrapper while a node is held
$b = $doc->getElementsByTagName('b')->item(0);
$c = $root->firstChild->lastChild;
unset($doc, $root);
var_dump($b->nodeName, $b->textContent, $b->ownerDocument->documentElement->nodeName);

// a removed subtree goes with its last wrapper; a held descendant survives it
$doc = new DOMDocument;
$doc->loadXML('<r><a><b>one</b></a></r>');
$a = $doc->documentElement->removeChild($doc->documentElement->firstChild);
$b = $a->firstChild;
unset($a);
var_dump($b->nodeName, $b->parentNode, $doc->saveXML());

// a held descendant keeps namespaces its removed ancestor declared
$doc = new DOMDocument;
$doc->loadXML('<r><p:a xmlns:p="urn:p"><p:b>x</p:b></p:a></r>');
$a = $doc->documentElement->removeChild($doc->documentElement->firstChild);
$b = $a->firstChild;
unset($a);
var_dump($b->namespaceURI, $b->prefix, $b->nodeName);

// loading replaces the document; nodes of the old one keep it
$doc = new DOMDocument;
$doc->loadXML('<old><kept/></old>');
$kept = $doc->documentElement->firstChild;
$doc->loadXML('<new/>');
var_dump($kept->nodeName, $kept->ownerDocument->documentElement->nodeName, $doc->documentElement->nodeName);
var_dump($kept->ownerDocument === $doc);

// a failed load leaves the document as it was
$doc = new DOMDocument;
$doc->loadXML('<still/>');
var_dump(@$doc->loadXML('<broken'), $doc->documentElement->nodeName);

// appended text nodes stay separate nodes
$doc = new DOMDocument;
$doc->loadXML('<r/>');
$t = $doc->documentElement->appendChild($doc->createTextNode('p'));
$u = $doc->documentElement->appendChild($doc->createTextNode('q'));
var_dump($t->nodeValue, $u->nodeValue, $doc->documentElement->childNodes->length, $doc->saveXML($doc->documentElement));

// replacing content detaches the children still held
$doc->documentElement->textContent = 'new';
var_dump($t->nodeValue, $t->parentNode, $doc->saveXML($doc->documentElement));

// attributes: a replaced or removed attribute a wrapper holds lives on
$doc = new DOMDocument;
$doc->loadXML('<r id="1"/>');
$el = $doc->documentElement;
$id = $el->getAttributeNode('id');
$el->removeAttribute('id');
var_dump($id->value, $id->ownerElement, $el->hasAttribute('id'));
$el->setAttribute('k', 'v1');
$k = $el->getAttributeNode('k');
$el->setAttribute('k', 'v2');
var_dump($k->value, $el->getAttributeNode('k') === $k);
$attr = $doc->createAttribute('k');
$attr->value = 'v3';
$el->appendChild($attr);
var_dump($el->getAttribute('k'));

// a fragment hands its children over
$doc = new DOMDocument;
$doc->loadXML('<r/>');
$frag = $doc->createDocumentFragment();
$frag->appendChild($doc->createElement('x'));
$frag->appendChild($doc->createElement('y'));
$doc->documentElement->appendChild($frag);
var_dump($frag->childNodes->length, $doc->documentElement->childNodes->length, $doc->saveXML($doc->documentElement));

// structural errors
$other = new DOMDocument;
$other->loadXML('<o/>');
foreach ([
    fn() => $doc->documentElement->firstChild->appendChild($doc->documentElement),
    fn() => $doc->documentElement->appendChild($other->documentElement),
    fn() => $doc->documentElement->removeChild($other->documentElement),
    fn() => $doc->documentElement->insertBefore($doc->createElement('z'), $other->documentElement),
] as $attempt) {
    try {
        $attempt();
        echo "no error\n";
    } catch (DOMException $e) {
        echo get_class($e), ': ', $e->getMessage(), ' (', $e->code, ")\n";
    }
}
var_dump($doc->createTextNode('t')->appendChild($doc->createElement('z')));

// replaceChild hands back the old node, detached
$doc = new DOMDocument;
$doc->loadXML('<r><old/></r>');
$old = $doc->documentElement->replaceChild($doc->createElement('new'), $doc->documentElement->firstChild);
var_dump($old->nodeName, $old->parentNode, $doc->saveXML($doc->documentElement));

// clones are detached copies
$copy = $doc->documentElement->cloneNode(true);
var_dump($copy === $doc->documentElement, $copy->parentNode, $copy->firstChild->nodeName);
$docCopy = clone $doc;
$docCopy->documentElement->setAttribute('copy', '1');
var_dump($doc->saveXML($doc->documentElement), $docCopy->saveXML($docCopy->documentElement));

// SimpleXML elements keep their document too, and share it with DOM
$sx = simplexml_load_string('<r><k>v</k><k>w</k></r>');
$k = $sx->k[1];
unset($sx);
var_dump((string) $k, $k->asXML());
$doc = new DOMDocument;
$doc->loadXML('<r><k>shared</k></r>');
$sx = simplexml_import_dom($doc);
unset($doc);
var_dump((string) $sx->k, dom_import_simplexml($sx)->ownerDocument->documentElement->nodeName ?? null);

// an expanded XMLReader node is a copy that outlives the reader's position
$reader = new XMLReader;
$reader->XML('<r><a>one</a><b>two</b></r>');
while ($reader->read() && $reader->name !== 'a');
$expanded = $reader->expand();
while ($reader->read());
var_dump($expanded->nodeName, $expanded->textContent);

// none of it grows with repetition
$xml = '<r>' . str_repeat('<item id="1">text <b>bold</b></item>', 20) . '</r>';
$cycle = function () use ($xml) {
    $d = new DOMDocument;
    $d->loadXML($xml);
    $d->documentElement->removeChild($d->documentElement->firstChild);
    (new DOMXPath($d))->query('//b')->item(0)->textContent;
    $d->createElement('orphan')->appendChild($d->createTextNode('t'));
    $s = simplexml_load_string($xml);
    foreach ($s->item as $item) (string) $item;
};
for ($i = 0; $i < 50; $i++) $cycle();
$before = memory_get_usage();
for ($i = 0; $i < 500; $i++) $cycle();
echo memory_get_usage() - $before < 16 * 1024 ? "flat\n" : "grew\n";
