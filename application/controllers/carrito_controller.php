
<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Carrito_controller extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('carrito_model');
		$this->load->model('producto_model');
        $this->load->library('cart');
	}

	public function index()
	{
		
	}

	//Este método llama a la página Lacteos Enteros, con el carrito si está logueado
	public function lacteos_enteros()
	{
		$dat = array('productos' => $this->producto_model->get_lacteos_enteros());

		$data = array('titulo' => 'Lacteos Enteros');
		$session_data = $this->session->userdata('logged_in');
		$data['perfil_id'] = $session_data['perfil_id'];
		$data['nombre'] = $session_data['nombre'];
		
		$this->load->view('Front/header_views', $data);
		$this->load->view('Front/navbar_views2', $data);
		if ($session_data) 
		{
			$this->load->view('carritoparte_view' );
		}
		
		$this->load->view('lacteos_enteros_view', $dat);
		$this->load->view('Front/footer_views');
	}

		public function lacteos_descremados()
	{
		$dat = array('productos' => $this->producto_model->get_lacteos_descremados());

		$data = array('titulo' => 'Lacteos Descrmados');
		$session_data = $this->session->userdata('logged_in');
		$data['perfil_id'] = $session_data['perfil_id'];
		$data['nombre'] = $session_data['nombre'];
		
		$this->load->view('Front/header_views', $data);
		$this->load->view('Front/navbar_views2', $data);
		if ($session_data) 
		{
			$this->load->view('carritoparte_view' );
		}
		
		$this->load->view('lacteos_descremados_view', $dat);
		$this->load->view('Front/footer_views');
	}


		
	//Agrega elemento al carrito
	function add()
	{
        // Genera array para insertar en el carrito
		$insert_data = array(
			'id' => $this->input->post('id'),
			'name' => $this->input->post('descripcion'),
			'price' => $this->input->post('precio_venta'),
			'qty' => 1
			);	

        // Inserta elemento al carrito
		$this->cart->insert($insert_data);
	      
        // Redirige a la misma página que se encuentra
		header('Location: '.$_SERVER['HTTP_REFERER']);
	}

	
	//Elimina elemento del carrito o el carrito entero
	function remove($rowid) {
        //Si $rowid es "all" destruye el carrito
		if ($rowid==="all")
		{
			$this->cart->destroy();
		}
		else //Sino destruye sola fila seleccionada
		{ 
			$data = array(
				'rowid'   => $rowid,
				'qty'     => 0
				);
            // Actualiza los datos
			$this->cart->update($data);
		}
		
        // Redirige a la misma página que se encuentra
		header('Location: '.$_SERVER['HTTP_REFERER']);
	}
	

	//Actualiza el carrito que se muestra
	function actualiza_carrito()
    {        
	    // Recibe los datos del carrito, calcula y actualiza
       	$cart_info =  $_POST['cart'];

		foreach( $cart_info as $id_producto => $cart)
		{	
            $id = $cart['id'];
		    $rowid = $cart['rowid'];
	    	$price = $cart['price'];
	    	$amount = $price * $cart['qty'];
	    	$qty = $cart['qty'];
	    
	    	$data = array(
					'rowid'   => $rowid,
	                'price'   => $price,
	                'amount' =>  $amount,
					'qty'     => $qty
					);

	    	$productos = $this->producto_model->edit_producto($id);
			//$dat = array('productos' => $this->producto_model->get_productos());
			foreach($productos->result() as $row)
			{
				$stock = $row->stock;
				if($stock <= $qty)
				{
					header('Location: '. base_url('noStock'));
					return;
				} else 
				  {
					$this->cart->update($data);
					header('Location: '. base_url('carrito'));
				  } 
			}

	         $this->cart->update($data);
			
		// Redirige a la misma página que se encuentra
		header('Location: '.$_SERVER['HTTP_REFERER']);
	   }
    }


    
    function stockInsuficiente()
	{

		$this->load->view('Front/mensajes');
		header('Location: '.$_SERVER['HTTP_REFERER']);


	}




	//Muestra los detalles de la venta y confirma(función guarda_compra())
	function muestra_compra()
	{
		$data = array('titulo' => 'Confirmar compra');
		
		$session_data = $this->session->userdata('logged_in');
		$data['perfil_id'] = $session_data['perfil_id'];
		$data['nombre'] = $session_data['nombre'];
		$data['apellido'] = $session_data['apellido'];
		$data['email'] = $session_data['email'];
		
		$this->load->view('Front/header_views', $data);
		$this->load->view('Front/navbar_views2', $data);
		$this->load->view('compra_view', $data);
		$this->load->view('Front/footer_views');
    }
    

    //Guarda los datos de la venta en la base de datos    
    function guarda_compra()
	{
		$session_data = $this->session->userdata('logged_in');
		$data['id'] = $session_data['id'];

		$total = $this->input->post('total_venta');

		
		$venta = array(
			'fecha' 		=> date('Y-m-d'),
			'usuario_id' 	=> $data['id'],
			'total_venta'	=> $total
		);	
		$venta_id = $this->carrito_model->insert_venta($venta);
		
		
		if ($cart = $this->cart->contents()):
			foreach ($cart as $item):
				$venta_detalle = array(
					'venta_id' 		=> $venta_id,
					'producto_id' 	=> $item['id'],
					'cantidad' 		=> $item['qty'],
					'precio' 		=> $item['price'],
					'total' 		=> $item['subtotal']
					);	
            
            	$cust_id = $this->carrito_model->insert_venta_detalle($venta_detalle);

            	//Descuenta del stock y lo guarda en la base de datos
            	$producto = $this->producto_model->edit_producto($item['id']);
            	foreach ($producto->result() as $row) 
				{
					$stock = $row->stock;
				}

            	$stock_edit = $stock - $item['qty'];

            	$stock_nuevo = array(
            		'stock'	=> $stock_edit
            		);

            	$modifica = $this->producto_model->update_producto($item['id'], $stock_nuevo);

			endforeach;
		endif;
	    

		$data = array('titulo' => 'Compra Finalizada');

		$data['perfil_id'] = $session_data['perfil_id'];
		$data['nombre'] = $session_data['nombre'];

        $this->load->view('Front/header_views', $data);
		$this->load->view('Front/navbar_views2', $data);
		$this->load->view('compralista_view');
		$this->load->view('Front/footer_views');

		$final = $this->cart->destroy();

	}



}