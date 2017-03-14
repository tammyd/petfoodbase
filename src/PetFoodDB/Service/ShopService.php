<?php


namespace PetFoodDB\Service;


class ShopService extends BaseService
{

    public function updateWalmart($id, $text) {

        $id = intval($id);

        $this->db->shop->insert_update(['id'=>$id], ['walmart'=>$text]);

    }

    public function updateAmazon($id, $text) {

        $id = intval($id);

        $this->db->shop->insert_update(['id'=>$id], ['amazon'=>$text]);

    }

    public function updateChewy($id, $text) {

        $id = intval($id);

        $this->db->shop->insert_update(['id'=>$id], ['chewy'=>$text]);

    }

    public function updatePetsmart($id, $text) {

        $id = intval($id);

        $this->db->shop->insert_update(['id'=>$id], ['petsmart'=>$text]);

    }

    public function getAll($id) {
        $data =  $this->db->shop[$id];
        if ($data) {
            return iterator_to_array($data);
        }

        return [];
    }

}
