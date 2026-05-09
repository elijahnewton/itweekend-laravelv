# 1. Provide the Cloud (AWS)
provider "aws" {
  region = "us-east-1"
}

# 2. The Shared Database (RDS)
resource "aws_db_instance" "lms_db" {
  allocated_storage    = 20
  engine               = "postgres"
  instance_class       = "db.t3.micro"
  db_name              = "lms_production"
  username             = "postgres"
  password             = var.db_password
  skip_final_snapshot  = true
}

# 3. The Load Balancer (The "Switch")
resource "aws_lb" "lms_alb" {
  name               = "lms-alb"
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = [aws_subnet.public_a.id, aws_subnet.public_b.id]
}

# 4. Two Target Groups (Blue and Green)
resource "aws_lb_target_group" "blue" {
  name     = "tg-blue"
  port     = 80
  protocol = "HTTP"
  vpc_id   = aws_vpc.main.id
}

resource "aws_lb_target_group" "green" {
  name     = "tg-green"
  port     = 80
  protocol = "HTTP"
  vpc_id   = aws_vpc.main.id
}