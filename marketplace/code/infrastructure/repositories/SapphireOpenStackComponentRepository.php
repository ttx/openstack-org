<?php
/**
 * Copyright 2014 Openstack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
class SapphireOpenStackComponentRepository
	extends SapphireRepository
	implements IOpenStackComponentRepository {

	public function __construct(){
		parent::__construct(new OpenStackComponent);
	}
	/**
	 * @param string $name
	 * @return IOpenStackComponent
	 */
	public function getByName($name)
	{
		$class = $this->entity_class;
		return $class::get()->filter('Name',$name)->first();
	}

    /**
     * @param string $name
     * @param string $codename
     * @return IOpenStackComponent
     */
    public function getByNameAndCodeName($name, $codename)
    {
        $class = $this->entity_class;
        return $class::get()->filter(['Name' => $name, 'CodeName' => $codename])->first();
    }

}