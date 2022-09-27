<?php 

namespace usy4\Suggestions;

use usy4\Suggestions\commands\SuggestionsCommand;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\utils\Config;

use usy4\Suggestions\libs\jojoe77777\FormAPI\SimpleForm;
use usy4\Suggestions\libs\jojoe77777\FormAPI\CustomForm;
use usy4\Suggestions\libs\jojoe77777\FormAPI\ModalForm;

class Main extends PluginBase{
    
    public $getSugs;
    
    public function onEnable() : void {
        $this->saveResource("config.yml");
        $this->getSugs = new Config($this->getDataFolder().'/suggestions.yml', 2);
        $this->cfg();
        $this->getSugs();
        $this->getServer()->getCommandMap()->register($this->getName(), new SuggestionsCommand($this)); 
    }
    
    public function cfg(){
        return $this->getConfig();
    }
    
    public function getSugs(){
        return $this->getSugs;
    }

    public function getLikes(Player|string $player, string $suggName, bool $sort = true){
        $name = $player instanceof Player ? $player->getName() : $player;
        $all = $this->getSugs()->getAll();

        $likes = [];

        foreach ($all as $id => $data){
            foreach ($data as $name_ => $d){
                if(strtolower($name) == strtolower($name_)){
                    foreach ($d as $dd){
                        if($suggName == $dd["suggestion"]){
                            $likes = $dd["likes"];
                            break;
                        }
                    }
                }
            }
        }

        if ($sort) arsort($likes);

        return $likes;
    }
    
    public function getDisLikes(Player|string $player, string $suggName, bool $sort = true){
        $name = $player instanceof Player ? $player->getName() : $player;
        $all = $this->getSugs()->getAll();

        $dislikes = [];

        foreach ($all as $id => $data){
            foreach ($data as $name_ => $d){
                if(strtolower($name) == strtolower($name_)){
                    foreach ($d as $dd){
                        if($suggName == $dd["suggestion"]){
                            $dislikes = $dd["dislikes"];
                            break;
                        }
                    }
                }
            }
        }

        if ($sort) arsort($dislikes);

        return $dislikes;
    }

    public function getSuggestions(Player|string $player){
        $name = $player instanceof Player ? $player->getName() : $player;
        $all = $this->getSugs()->getAll();

        $index = [];

        foreach ($all as $id => $data){
            foreach ($data as $name_ => $d){
                if(strtolower($name) == strtolower($name_)){
                    foreach ($d as $dd){
                        $index[] = $dd["suggestion"];
                    }

                    break;
                }
            }
        }

        return $index;
    }
    
    public function getNewerSuggestions(){
        $all = $this->getSugs()->getAll();

        $index = [];

        foreach ($all as $id => $data){
            foreach ($data as $name_ => $d){
                foreach ($d as $dd){
                    $dd["author"] = $name_;
                    $dd["id"] = $id;
                    $index[] = $dd;
                }
            }
        }

        $index_ = [];

        foreach ($index as $d){
            $index_[$d["id"]] = $d; 
        }
        
        arsort($index_);

        return $index_;
    }

    public function getSuggestionId(string $suggestion){
        $all = $this->getSugs()->getAll();

        foreach ($all as $id => $data){
            foreach ($data as $name_ => $d){
                foreach ($d as $dd){
                    if($dd["suggestion"] == $suggestion){
                        return $id;
                    }
                }
            }
        }

        return null;
    }

    public function getSuggestionById(int $id){
        $all = $this->getSugs()->getAll();
        return isset($all[$id]) ? $all[$id][0]["suggestion"] : null;
    }

    public function getMostPopularSuggestions(){
        $all = $this->getSugs()->getAll();
        
        $suggestions = [];

        foreach ($all as $id => $data){
            foreach ($data as $name_ => $d){
                foreach ($d as $dd){
                    $suggestions[] = [
                        "suggestion" => $dd["suggestion"],
                        "likes" => $this->getLikes($name_, $dd["suggestion"]),
                        "dislikes" => $this->getDisLikes($name_, $dd["suggestion"]),
                        "author" => $name_,
                        "id" => $id
                    ];
                }
            }
        }

        return $suggestions;
    }

    public function rmSuggestionById(int $id, ?Player $sender = null){
        $all = $this->getSugs()->getAll();

        if(isset($all[$id])){
            unset($all[$id]);

            $this->getSugs()->setAll($all);
            $this->getSugs()->save();

            if($sender !== null) $sender->sendMessage(TextFormat::YELLOW . "Suggestion removed!");

            return true;
        }

        return false;
    }
       
    public function AllMax(){
        return $this->cfg()->get("AllMax", 25);
    }
    public function PlayerMax(){
        return $this->cfg()->get("PlayerMax", 3);
    }
    
    public function ui(Player $player){
        $form = new SimpleForm(function (Player $player, int $data = null){
            if($data === null)
                return false;
            switch($data){      
                case 0:
                    $this->suggestionsList($player);                    
                    break;
                case 1:
                    $this->addSuggestion($player);
                    break;
                case 2:
                    $this->yourSuggestions($player);
                    break;
                case 3:
                    $this->Administrator($player);
                    break;
            }
        });
        $form->addButton("Suggestions list");
        $form->addButton("Add a suggest");
        $form->addButton("Your suggestions");
        if($player->hasPermission("suggestions.admin"))
            $form->addButton("Administrator");
        $form->sendToPlayer($player);

    }    

    public function suggestionsList(Player $player){
        $form = new SimpleForm(function (Player $player, int $data = null){
                        if($data === null)
                            return false;
                        switch($data){
                            case 0:
                                $this->mostPopularList($player);
                                break;
                        	case 1:
                        		$this->newerList($player);
                				break;
                        }  
        });
        $form->setTitle("Suggestions list sort by:");    
        $form->addButton("The Most Popular");
        $form->addButton("The Newer");
        $form->sendToPlayer($player);
    }
    
    public function mostPopularList(Player $player){
        $most = $this->getMostPopularSuggestions();
        $all = $this->getSugs()->getAll();

        $form = new SimpleForm(function (Player $player, int $data = null) use ($most, $all){
            if($data === null)
                return false;
            
            if(isset($most[$data])){
                $sugg = $most[$data];

                $form = new SimpleForm(fn(Player $player, ?int $data = null) => match ($data){
                    null => "",
                    0 => $this->like($sugg["id"], $player),
                    1 => $this->dislike($sugg["id"], $player),
                    2 => $this->rmSuggestionById($sugg["id"], $player)
                });

                $form->setTitle("Suggestion id: " . $sugg["id"]);

                $form->setContent("The Suggestion: " . $sugg["suggestion"]
                                  . "\n\nSuggestion By: " . $sugg["author"]
                                  . "\nLikes: " . count($sugg["likes"])
                                  . "\nDisLikes: " . count($sugg["dislikes"]));
                
                $form->addButton("Like");
                $form->addButton("DisLike");
                if($player->hasPermission("suggestions.admin") or $sugg["author"] == $player->getName()) $form->addButton("Delete");

                $form->sendToPlayer($player);
            }
        });

        if(count($most) === 0){
            $form->setContent("Couldn't find any suggestion!");
            $form->addButton("Okay");
        } else {
            foreach ($most as $suggestion){
                $form->addButton($suggestion["suggestion"] . "\n" . $suggestion["author"] . ", " . "Likes: " . count($suggestion["likes"]) . " | DisLikes: " . count($suggestion["dislikes"])); 
            }
        }

        $form->sendToPlayer($player);  
    }

    public function newerList(Player $player){
        $all = $this->getSugs()->getAll();

        $suggestions = $this->getNewerSuggestions();
        $suggestions = array_values($suggestions);

        $form = new SimpleForm(function (Player $player, int $data = null) use ($all, $suggestions){
            if($data === null)
                return false;
            
            if(isset($suggestions[$data])){
                $sugg = $suggestions[$data];

                $form = new SimpleForm(fn(Player $player, ?int $data = null) => match ($data){
                    null => "",
                    0 => $this->like($sugg["id"], $player),
                    1 => $this->dislike($sugg["id"], $player),
                    2 => $this->rmSuggestionById($sugg["id"], $player)
                });

                $form->setTitle("Suggestion id: " . $sugg["id"]);

                $form->setContent("The Suggestion: " . $sugg["suggestion"]
                                  . "\n\nSuggestion By: " . $sugg["author"]
                                  . "\nLikes: " . count($sugg["likes"])
                                  . "\nDisLikes: " . count($sugg["dislikes"]));
                
                $form->addButton("Like");
                $form->addButton("DisLike");
                if($player->hasPermission("suggestions.admin") or $sugg["author"] == $player->getName()) $form->addButton("Delete");

                $form->sendToPlayer($player);
            }
        });

        $form->setTitle("The Newer Suggestions");       
        $count = count($suggestions);
        $form->setContent("There is " . $count . " suggestions");
        
        if($count === 0){
            $form->addButton("Okay");
        } else {
            foreach ($suggestions as $dd){
                $form->addButton($dd["suggestion"] . "\n" . $dd["author"] . ", " . "Likes: " . count($dd["likes"]) . " | DisLikes: " . count($dd["dislikes"]));
            }
        }

        $form->sendToPlayer($player);  
    }


    public function addSuggestion(Player $player){
        $all = $this->getSugs()->getAll();
        $form = new CustomForm(function (Player $player, $data) use ($all){
            if($data === null)
                return false;
            if(count($this->getSuggestions($player)) <= $this->PlayerMax() or count($all) <= $this->AllMax())
                return $player->sendMessage("Sorry, but that the max limit of your suggestions or suggestions all");  
            if($data[0] == "")
                return $player->sendMessage("Empty.");  
            $all = $this->getSugs()->getAll() ?? [];
            $range = range(1,$this->AllMax());
            foreach($range as $arr){
                if(!isset($all[$arr])){
                    $all[$arr] = [
                        $player->getName() => []
                    ];
                    $sugg = $all[$arr][$player->getName()];
                    $sugg[] = [
                        "suggestion" => $data[0],
                        "likes" => [],
                        "dislikes" => []
                    ];
                    $all[$arr][$player->getName()] = $sugg;
                    $this->getSugs()->setAll($all);
                    $this->getSugs()->save();
                    $player->sendMessage("thanks for your suggest.");  
                    break;
                }
            }
        });
        $form->setTitle("Add a suggestion");
        $form->setContent("You have " .count($this->getSuggestions($player)) . "suggestion");
        if(count($this->getSuggestions($player)) <= $this->PlayerMax() or count($all) <= $this->AllMax()){
            $form->addInput("Suggest", "Add new games...");
        } else {
            $player->sendMessage("Sorry, but that the max limit of your suggestions or suggestions all"); 
        }
        $form->sendToPlayer($player);
    }

   public function yourSuggestions(Player $player){
        $most = $this->getMostPopularSuggestions();
        $all = $this->getSugs()->getAll();

        $form = new SimpleForm(function (Player $player, int $data = null) use ($most, $all){
            if($data === null)
                return false;
            
            if(isset($most[$data])){
                $sugg = $most[$data];

                $form = new SimpleForm(fn(Player $player, ?int $data = null) => match ($data){
                    null => "",
                    0 => $this->like($sugg["id"], $player),
                    1 => $this->dislike($sugg["id"], $player),
                    2 => $this->rmSuggestionById($sugg["id"], $player)
                });

                $form->setTitle("Suggestion id: " . $sugg["id"]);

                $form->setContent("The Suggestion: " . $sugg["suggestion"]
                                  . "\n\nSuggestion By: " . $sugg["author"]
                                  . "\nLikes: " . count($sugg["likes"])
                                  . "\nDisLikes: " . count($sugg["dislikes"]));
                
                $form->addButton("Like");
                $form->addButton("DisLike");
                if($player->hasPermission("suggestions.admin") or $sugg["author"] == $player->getName()) $form->addButton("Delete");
                
                $form->sendToPlayer($player);
            }
        });

        if(count($this->getSuggestions($player)) === 0){
            $form->setContent("Couldn't find any suggestion!");
            $form->addButton("Okay");
        } else {
            foreach ($most as $suggestion){
                if($suggestion["author"] !== $player->getName()) continue;
                $form->addButton($suggestion["suggestion"] . "\n" . $suggestion["author"] . ", " . "Likes: " . count($suggestion["likes"]) . " | DisLikes: " . count($suggestion["dislikes"])); 
            }
        }

        $form->sendToPlayer($player);  
    }
    
    public function Administrator(Player $player){
        $form = new SimpleForm(function (Player $player, int $data = null){
            if($data === null)
                return false;            
            switch($data){
                case 0:
                    $this->deleteAll($player);
                    break;
            }  
        });
        $form->setTitle("Administrator");    
        $form->addButton("Delete all");
        $form->sendToPlayer($player);
    }
    
    public function deleteAll(Player $player){
        $form = new ModalForm(function (Player $player, $data){
            if($data === null)
                return false;            
            switch($data){
                case true:
                    $this->getSugs()->setAll([]);
                    $this->getSugs()->save();
                    $player->sendMessage("Done, you delete all suggestions");
                    break;
            }  
        });

        $form->setTitle("Delete All");
        $form->setContent("Are you sure?");
        $form->setButton1("Yes");
        $form->setButton2("No");
        $form->sendToPlayer($player);
    }
    
    public function like(int $id, string $player){
        $pn = $player;
        $all = $this->getSugs()->getAll() ?? [];
        $name = "";
        foreach ($all as $index => $id_){
            foreach ($id_ as $dd => $n){
                if(isset($all[$id][$dd])){
                    $name = $dd;
                }
            }
        }
        $num = 999;
        foreach(range(0,count($all[$id][$name][0]["likes"])-1) as $i){
            if(count($all[$id][$name][0]["likes"]) !== 0) {
                if($all[$id][$name][0]["likes"][$i] == $pn){
                    $num = $i;
                }
            }
        }
        $num2 = 999;
        foreach(range(0,count($all[$id][$name][0]["dislikes"])-1) as $i){
            if(count($all[$id][$name][0]["dislikes"]) !== 0) {
                if($all[$id][$name][0]["dislikes"][$i] == $pn){
                $num2 = $i;
                }
            }            
        }
        
        if(isset($all[$id][$name][0]["likes"][$num]))
            return $player->sendMessage("You are already liked this suggestion.");   
        if(isset($all[$id][$name][0]["dislikes"][$num2])) 
            unset($all[$id][$name][0]["dislikes"][$num2]);
        $like = $all[$id][$name][0]["likes"];
        $like[] = $pn;
        $all[$id][$name][0]["likes"] = $like;
        $this->getSugs()->setAll($all);
        $this->getSugs()->save(); 
        $player->sendMessage("Done, you liked this suggestion.");
    }
    
     public function dislike(int $id, Player $player){
        $pn = $player->getName();
        $all = $this->getSugs()->getAll() ?? [];
        $name = "";
        foreach ($all as $index => $id_){
            foreach ($id_ as $dd => $n){
                if(isset($all[$id][$dd])){
                    $name = $dd;
                }
            }
        }
        $num = 999;
        foreach(range(0,count($all[$id][$name][0]["dislikes"])-1) as $i){
            if(count($all[$id][$name][0]["dislikes"]) !== 0) {
                if($all[$id][$name][0]["dislikes"][$i] == $pn){
                $num = $i;
                }
            }            
        }
        $num2 = 999;
        foreach(range(0,count($all[$id][$name][0]["likes"])-1) as $i){
            if(count($all[$id][$name][0]["likes"]) !== 0) {
                if($all[$id][$name][0]["likes"][$i] == $pn){
                    $num2 = $i;
                }
            }
        }
        if(isset($all[$id][$name][0]["dislikes"][$num]))
            return $player->sendMessage("You are already disliked this suggestion.");   
        if(isset($all[$id][$name][0]["likes"][$num2])) 
            unset($all[$id][$name][0]["likes"][$num2]);
        $like = $all[$id][$name][0]["dislikes"];
        $like[] = $pn;
        $all[$id][$name][0]["dislikes"] = $like;
        $this->getSugs()->setAll($all);
        $this->getSugs()->save(); 
        $player->sendMessage("Done, you disliked this suggestion.");
    }
    
}