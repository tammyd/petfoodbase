<?php


namespace PetFoodDB\Service;


class PriceService extends BaseService
{

    public function updatePrice($id, array $data) {

        $id = intval($id);

        $this->db->prices->insert_update(
            ['id'=>$id],
            ['min'=>$data['low'], 'max'=>$data['high'], 'avg'=>$data['avg'], 'url'=>$data['url'], 'date'=>$data['date']]);

    }

    public function getPrice($id) {
        $data =  $this->db->prices[$id];
        if ($data) {
            return iterator_to_array($data);
        }
    }

}
