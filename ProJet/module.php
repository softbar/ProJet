<?php
/**
 * @author Xavier
 *
 */
class ProJet extends IPSModule {
	/**
	 * {@inheritDoc}
	 * @see IPSModule::Create()
	 */
	public function Create(){
		parent::Create();
 		$this->registerPropertyInteger('DeviceID',144);
 		$this->registerPropertyInteger('Mode',0);
 		$this->registerPropertyInteger('DimSpeed',0);
	}
	/**
	 * {@inheritDoc}
	 * @see IPSModule::ApplyChanges()
	 */
	public function ApplyChanges(){
		parent::ApplyChanges();
		$this->ConnectParent("{995946C3-7995-48A5-86E1-6FB16C3A0F8A}");
		$this->registerVariableBoolean('STATE',$this->Translate('State'),'~Switch',0);
 		$this->registerVariableInteger('LEVEL',$this->Translate('Level'),'~Intensity.100',1);
		$this->registerVariableInteger('COLOR',$this->Translate('Color'),'~HexColor',2);
 		$this->registerVariableInteger('WHITE',$this->Translate('White'),'~Intensity.255',9);
 		$this->MaintainActions(['STATE','LEVEL','COLOR','WHITE'],true);
 		if($this->ReadPropertyInteger('Mode')==0){ // Color Mode
			$this->MaintainActions(['RED','GREEN','BLUE'],false);
 			@$this->unregisterVariable('RED');
 			@$this->unregisterVariable('GREEN');
 			@$this->unregisterVariable('BLUE');
 		}else{ // RGB Split Mode
 			$RGB=$this->toRGB($this->getValueByIdent('COLOR'));
 			if($id=$this->registerVariableInteger('RED',$this->Translate('Red'),'~Intensity.255',2))SetValue($id, $RGB[RGB_RED]);
 			if($id=$this->registerVariableInteger('GREEN',$this->Translate('Green'),'~Intensity.255',3))SetValue($id, $RGB[RGB_GREEN]);
 			if($id=$this->registerVariableInteger('BLUE',$this->Translate('Blue'),'~Intensity.255',4))SetValue($id, $RGB[RGB_BLUE]);
			$this->MaintainActions(['RED','GREEN','BLUE'],true);
 		}
  	}
	/**
	 * {@inheritDoc}
	 * @see IPSModule::RequestAction()
	 */
	public function RequestAction($ident, $value){
		switch($ident){
			case 'STATE': $this->SetState($value); break;
   			case 'LEVEL': $this->SetLevel($value); break;
 			case 'COLOR': $this->SetColor($value); break;
 			case 'WHITE': $this->_setWhite($value); break;
 			case 'RED'	: $this->_setColor(RGB_RED, $value); break;
   			case 'GREEN': $this->_setColor(RGB_GREEN,$value); break;
   			case 'BLUE'	: $this->_setColor (RGB_BLUE,$value); break;
   			default : echo "Unknown action $ident";
		}
	}

 	/** @brief Switch power on / of
 	 * @param bool $On If true then on otherwise off
 	 * @return boolean If command sucessfully
 	 */
 	public function SetState(bool $On){
 		if($this->getValueByIdent('STATE')===$On)return true;
 		return $On? $this->_upState():$this->_downState();
 	}
 	/** @brief Set current color value
 	 * @param int $Color New color
 	 * @return boolean
 	 */
 	public function SetColor(int  $Color){
  		if($this->getValueByIdent('COLOR')==$Color) return false;
  		if($Color==0)$this->_saveState();
  		$rgb=$this->toRGB($Color);
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			return $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, intval($this->getValueByIdent('WHITE')), $dimSpeed);	
		return $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], intval($this->getValueByIdent('WHITE')));	
  	}
  	/**
  	 * @param int $NewWhite
  	 */
  	public function SetWhite(int $NewWhite){
  		$this->_setWhite($NewWhite); 
  	}
 	/**
 	 * @param int $NewLevel
 	 * @return boolean
 	 */
 	public function SetLevel(int $NewLevel){
 		if($NewLevel>100)$NewLevel=100;elseif($NewLevel<0)$NewLevel=0;
  		if($this->getValueByIdent('LEVEL')==$NewLevel) return true;
   		if(!($color=$this->getValueByIdent('COLOR')) && $NewLevel>0){ 
		   	if(!($color=intval($this->GetBuffer('OnColor'))))$color=8421504;
			if(!($w=$this->getValueByIdent('WHITE'))){
				$w=	intval($this->GetBuffer('OnWhite'));	   
  			}
		}else $w=$this->getValueByIdent('WHITE');
  		$rgb=$this->toRGB($color);
		$oldLevel=max($rgb)/2.55;
		if((int)round($oldLevel)==$NewLevel){
			return false;
		}
		if($NewLevel==0){
			$this->_saveState();
		}
		foreach($rgb as $k=>$v){
			$rgb[$k]=(int)round(($v/$oldLevel)*$NewLevel);
		}
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			return $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, $w, $dimSpeed);	
		return $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], $w);	
 	}
 	/**
 	 * @param int $Red
 	 * @param int $Green
 	 * @param int $Blue
 	 * @param int $White
 	 * @return boolean
 	 */
 	public function SetRGBW(int $Red, int $Green, int $Blue, int $White){
		if($Red>255)$Red=255;else if($Red<0)$Red=0;
		if($Green>255)$Green=255;else if($Green<0)$Green=0;
		if($Blue>255)$Blue=255;else if($Blue<0)$Blue=0;
		if($White>255)$White=255;else if($White<0)$White=0;
 		if($ok=$this->_forwardData(['P',$Red,$Green,$Blue,$White]))	{
			$this->_updateByColor($Red, $Green, $Blue, $White);
		}
		return (bool)$ok;
	}
 	/**
 	 * @param int $Red
 	 * @param int $RZeit
 	 * @param int $Green
 	 * @param int $GZeit
 	 * @param int $Blue
 	 * @param int $BZeit
 	 * @param int $White
 	 * @param int $WZeit
 	 * @return boolean
 	 */
 	public function DimRGBW(int $Red, int $RZeit, int $Green, int $GZeit, int $Blue, int $BZeit, int $White, int $WZeit){
		$toParam=function($v,$t){
			return $t>0?hexdec(sprintf('%02x%02x',$t,$v)):$v;
		};	
		if($Red>255)$Red=255;else if($Red<0)$Red=0;
		if($Green>255)$Green=255;else if($Green<0)$Green=0;
		if($Blue>255)$Blue=255;else if($Blue<0)$Blue=0;
		if($White>255)$White=255;else if($White<0)$White=0;
		$data=['P',$toParam($Red,$RZeit),$toParam($Green,$GZeit),$toParam($Blue,$BZeit),$toParam($White,$WZeit)];
 		if($ok=$this->_forwardData($data)){
			$this->_updateByColor($Red, $Green, $Blue, $White);
 		}
 		return (bool)$ok;
	}
	/**
	 * @return boolean
	 */
	public function DimUp(){
		return (($level=$this->getValueByIdent('LEVEL'))<100) ? $this->SetLevel($level+5):true;
 	}
 	/**
 	 * @return boolean
 	 */
 	public function DimDown(){
		return (($level=$this->getValueByIdent('LEVEL'))>0) ? $this->SetLevel($level-5):true;
 	}
	/**
	 * @param int $Programm
	 * @return boolean
	 */
	public function RunProgram(int $Programm){
		if($ok=$this->_forwardData(['F',$Programm])){
			
		}
		return (bool)$ok;
	}
 
	private function MaintainActions($actions, $enabled){
		if(is_array($actions))foreach($actions as $a)@$this->MaintainAction($a,$enabled);
	}
	private function toRGB($Color){
		return [
			RGB_RED=>($Color >> 16) & 0xFF,
			RGB_GREEN=>($Color >> 8) & 0xFF,
			RGB_BLUE=>$Color & 0xFF	
		];
	}
	private function toColor($rgb){
		return ($rgb[RGB_RED] << 16) + ($rgb[RGB_GREEN] << 8) + $rgb[RGB_BLUE];
	}
	private function setValueByIdent( $ident, $value, $force=false){
		if( ($id=@$this->GetIDForIdent($ident)) && ($force || GetValue($id)!=$value))return SetValue($id,$value);
		return ($id>0);
	}
	private function getValueByIdent( $ident){
		return ($id=@$this->GetIDForIdent($ident))?GetValue($id):null;
	}
	private function _setWhite($Value){
		if($this->getValueByIdent('WHITE')==$Value)return;
		$rgb=$this->toRGB($this->getValueByIdent('COLOR'));
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			 $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, (int)$Value, $dimSpeed);	
		else $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], (int)$Value);	
	}
	private function _setColor($id, $Value){
		$rgb=$this->toRGB($this->getValueByIdent('COLOR'));
		if($rgb[$id]==$Value)return;
 		$rgb[$id]=(int)$Value;
 		$w=$this->getValueByIdent('WHITE');
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			 $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, $w, $dimSpeed);	
		else $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], $w);
	}
 	private function _updateByColor($r,$g,$b,$w){
 		$this->setValueByIdent('COLOR', $this->toColor([$r, $g, $b]));
 		$this->setValueByIdent('LEVEL', $level=(int)round(max($r,$g,$b)/2.55));
 		$this->setValueByIdent('STATE', $level > 0|| $w > 0);
 		$this->setValueByIdent('WHITE', $w);
 		if($this->ReadPropertyInteger('Mode')==1){ // Splitt Channels
 			$this->setValueByIdent('RED', $r);
 			$this->setValueByIdent('GREEN', $g);
 			$this->setValueByIdent('BLUE', $b);
 		}
	}
	private function _saveState(){
		if($v=$this->getValueByIdent('COLOR'))$this->SetBuffer('OnColor',$v);
		$this->SetBuffer('OnWhite',$this->getValueByIdent('WHITE'));
	}
	private function _downState(){
		$this->_saveState();
		$this->SetRGBW(0, 0, 0, 0);
	}
	private function _upState(){
		if(!($Color=intval($this->GetBuffer('OnColor'))))$Color=8421504;
		$w=intval($this->GetBuffer('OnWhite'));
		$rgb=$this->toRGB($Color);
 		if($dimSpeed=$this->ReadPropertyInteger('DimSpeed'))
			return $this->DimRGBW($rgb[RGB_RED], $dimSpeed, $rgb[RGB_GREEN], $dimSpeed, $rgb[RGB_BLUE], $dimSpeed, $w, $dimSpeed);	
		return $this->SetRGBW($rgb[RGB_RED], $rgb[RGB_GREEN], $rgb[RGB_BLUE], $w);	
		
		
	}
	private function _forwardData(array $value){
		$data=[
				'DataID'=>"{9DD17B0B-030F-4849-8BFF-88EB4BB414BA}",
				'Data'	=>$this->ReadPropertyInteger('DeviceID').','.implode(',',$value)
		];
		if(!$value=@$this->SendDataToParent(json_encode($data)))
			IPS_LogMessage(IPS_GetName($this->InstanceID),'Error sending data to Device');
		$this->SendDebug('ForwardData',($value?'OK send => ':'Error send => ').json_encode($data['Data']),0);	
			
		return $value;		
	}
}
const RGB_RED=0,RGB_GREEN=1,RGB_BLUE=2;
?>